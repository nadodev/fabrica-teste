<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Inventory\Application\Port\StockReservationLifecycle;
use App\Modules\Inventory\Domain\Exception\ReservationConflict;
use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Payment\Application\DTO\ProviderWebhookEvent;
use App\Modules\Payment\Application\Port\PaymentGateway;
use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Application\Port\PaymentWebhookInbox;
use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentStatus;
use App\Modules\Shared\Application\Port\TransactionManager;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

final readonly class ProcessAsaasWebhooks
{
    public function __construct(
        private PaymentWebhookInbox $inbox,
        private PaymentRepository $payments,
        private PaymentGateway $gateway,
        private OrderRepository $orders,
        private StockReservationLifecycle $reservations,
        private TransactionManager $transactions,
    ) {}

    public function handle(int $limit = 100): int
    {
        $processed = 0;
        for ($handled = 0; $handled < max(1, min($limit, 1000)); $handled++) {
            $event = $this->inbox->claim();
            if ($event === null) {
                break;
            }
            try {
                $this->apply($event);
                $this->inbox->processed($event->id);
                $processed++;
            } catch (Throwable $exception) {
                $this->inbox->retry($event->id, $exception->getMessage());
            }
        }

        return $processed;
    }

    private function apply(ProviderWebhookEvent $event): void
    {
        $payment = $this->payments->findByProviderId($event->providerPaymentId)
            ?? throw new RuntimeException('Payment for Asaas event was not found.');
        $billingType = (string) ($event->payment['billingType'] ?? '');
        $isApproval = $event->event === 'PAYMENT_RECEIVED'
            || ($event->event === 'PAYMENT_CONFIRMED' && $billingType !== 'PIX');

        if ($isApproval) {
            $this->approve($payment, $event);

            return;
        }

        if (in_array($event->event, [
            'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED',
            'PAYMENT_REPROVED_BY_RISK_ANALYSIS',
            'PAYMENT_DELETED',
            'PAYMENT_BANK_SLIP_CANCELLED',
        ], true)) {
            $this->decline($payment, $event);

            return;
        }

        if ($event->event === 'PAYMENT_REFUNDED') {
            $this->transactions->run(function () use ($payment): void {
                $locked = $this->payments->findByProviderId((string) $payment->providerPaymentId(), true) ?? throw new RuntimeException('Payment disappeared.');
                if (! in_array($locked->status(), [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
                    return;
                }
                $order = $this->orders->find($locked->orderId) ?? throw new RuntimeException('Order not found.');
                $locked->refund();
                if ($order->status() === OrderStatus::Paid) {
                    $order->markRefunded();
                }
                $this->payments->save($locked, 'asaas_webhook');
                $this->orders->save($order);
            });

            return;
        }

        if ($event->event === 'PAYMENT_PARTIALLY_REFUNDED') {
            $this->partiallyRefund($event);

            return;
        }

        if (in_array($event->event, ['PAYMENT_CHARGEBACK_REQUESTED', 'PAYMENT_CHARGEBACK_DISPUTE', 'PAYMENT_AWAITING_CHARGEBACK_REVERSAL'], true)) {
            $this->chargeback($event);

            return;
        }

        if (in_array($event->event, ['PAYMENT_REFUND_IN_PROGRESS', 'PAYMENT_REFUND_DENIED'], true)) {
            $this->recordOrderPaymentStatus(
                $event,
                $event->event === 'PAYMENT_REFUND_IN_PROGRESS' ? 'refund_pending' : 'refund_denied',
            );
        }
    }

    private function approve(Payment $payment, ProviderWebhookEvent $event): void
    {
        try {
            $this->transactions->run(function () use ($event): void {
                $locked = $this->payments->findByProviderId($event->providerPaymentId, true) ?? throw new RuntimeException('Payment disappeared.');
                if ($locked->status() === PaymentStatus::Paid) {
                    return;
                }
                $order = $this->orders->find($locked->orderId) ?? throw new RuntimeException('Order not found.');
                $this->transitionReservations($locked, $order, true);
                $locked->approveFromProvider($event->providerPaymentId);
                if ($order->status() === OrderStatus::AwaitingPayment) {
                    $order->markPaid();
                } else {
                    $order->recordPaymentStatus('paid');
                }
                $this->payments->save($locked, 'asaas_webhook');
                $this->orders->save($order);
            });
        } catch (ReservationConflict $exception) {
            $key = Uuid::uuid5(Uuid::NAMESPACE_URL, 'asaas-stock-compensation:'.$payment->id)->toString();
            $this->gateway->refund($event->providerPaymentId, $key, $payment->amount);
            $this->decline($payment, new ProviderWebhookEvent($event->id, 'STOCK_RESERVATION_EXPIRED', $event->providerPaymentId, $event->payment));
        }
    }

    private function partiallyRefund(ProviderWebhookEvent $event): void
    {
        $amount = max(0, (int) round(((float) ($event->payment['refundedValue'] ?? 0)) * 100));
        if ($amount < 1) {
            throw new RuntimeException('Asaas partial refund amount is missing.');
        }

        $this->transactions->run(function () use ($event, $amount): void {
            $payment = $this->payments->findByProviderId($event->providerPaymentId, true) ?? throw new RuntimeException('Payment disappeared.');
            if ($payment->status() === PaymentStatus::Refunded || $payment->refundedAmount() >= $amount) {
                return;
            }
            $order = $this->orders->find($payment->orderId) ?? throw new RuntimeException('Order not found.');
            $payment->partiallyRefund($amount);
            $order->recordPaymentStatus('partially_refunded');
            $this->payments->save($payment, 'asaas_webhook');
            $this->orders->save($order);
        });
    }

    private function chargeback(ProviderWebhookEvent $event): void
    {
        $this->transactions->run(function () use ($event): void {
            $payment = $this->payments->findByProviderId($event->providerPaymentId, true) ?? throw new RuntimeException('Payment disappeared.');
            $order = $this->orders->find($payment->orderId) ?? throw new RuntimeException('Order not found.');
            $code = mb_strtolower((string) ($event->payment['chargebackStatus'] ?? $event->event));
            $payment->markChargeback($code);
            $order->recordPaymentStatus($event->event === 'PAYMENT_AWAITING_CHARGEBACK_REVERSAL' ? 'chargeback_reversal' : 'chargeback');
            $this->payments->save($payment, 'asaas_webhook');
            $this->orders->save($order);
        });
    }

    private function recordOrderPaymentStatus(ProviderWebhookEvent $event, string $status): void
    {
        $this->transactions->run(function () use ($event, $status): void {
            $payment = $this->payments->findByProviderId($event->providerPaymentId, true) ?? throw new RuntimeException('Payment disappeared.');
            $order = $this->orders->find($payment->orderId) ?? throw new RuntimeException('Order not found.');
            $order->recordPaymentStatus($status);
            $this->orders->save($order);
        });
    }

    private function decline(Payment $payment, ProviderWebhookEvent $event): void
    {
        $this->transactions->run(function () use ($event): void {
            $locked = $this->payments->findByProviderId($event->providerPaymentId, true) ?? throw new RuntimeException('Payment disappeared.');
            if ($locked->status() === PaymentStatus::Declined) {
                return;
            }
            $order = $this->orders->find($locked->orderId) ?? throw new RuntimeException('Order not found.');
            $this->transitionReservations($locked, $order, false);
            $locked->declineFromProvider($event->providerPaymentId, mb_strtolower($event->event));
            if ($order->status() === OrderStatus::AwaitingPayment) {
                $order->cancelAfterPaymentFailure();
            }
            $this->payments->save($locked, 'asaas_webhook');
            $this->orders->save($order);
        });
    }

    private function transitionReservations(Payment $payment, Order $order, bool $confirm): void
    {
        if (! $payment->stockReserved) {
            return;
        }
        foreach ($order->items() as $item) {
            $id = Uuid::uuid5(Uuid::NAMESPACE_URL, $order->id.':'.$item->productId.':'.($item->variationKey ?? 'default'))->toString();
            $confirm ? $this->reservations->confirm($id) : $this->reservations->release($id);
        }
    }
}
