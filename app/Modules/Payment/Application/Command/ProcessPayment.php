<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Inventory\Application\Port\StockReservationLifecycle;
use App\Modules\Inventory\Domain\Exception\ReservationConflict;
use App\Modules\Ordering\Application\Port\CouponGateway;
use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Payment\Application\DTO\CreditCardData;
use App\Modules\Payment\Application\DTO\PaymentRequest;
use App\Modules\Payment\Application\Exception\PaymentCardDeclined;
use App\Modules\Payment\Application\Exception\PaymentGatewayTimeout;
use App\Modules\Payment\Application\Port\PaymentGateway;
use App\Modules\Payment\Application\Port\PaymentInstructionStore;
use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentStatus;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;
use Ramsey\Uuid\Uuid;
use Throwable;

final readonly class ProcessPayment
{
    public function __construct(
        private PaymentRepository $payments,
        private PaymentGateway $gateway,
        private PaymentInstructionStore $instructions,
        private OrderRepository $orders,
        private CartRepository $carts,
        private CouponGateway $coupons,
        private StockReservationLifecycle $reservations,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $orderId, #[\SensitiveParameter] ?CreditCardData $creditCard = null): Payment
    {
        [$payment, $attemptId] = $this->start($orderId);
        if ($attemptId === null) {
            return $payment;
        }

        $order = $this->orders->find($orderId) ?? throw new DomainException('Order not found for payment.');
        try {
            $result = $this->gateway->charge(new PaymentRequest(
                $orderId,
                $payment->amount,
                $payment->currency,
                $payment->method,
                $payment->idempotencyKey,
                [
                    'name' => $order->details()->customerName,
                    'email' => $order->details()->customerEmail,
                    'document' => $order->details()->customerDocument,
                    'phone' => $order->details()->customerPhone,
                    'postalCode' => $order->details()->shippingZip,
                    'addressNumber' => $order->details()->shippingNumber,
                ],
                creditCard: $creditCard,
            ));
        } catch (PaymentCardDeclined $exception) {
            $this->transactions->run(function () use ($payment, $order, $attemptId): void {
                $this->transitionReservations($payment, $order, false);
                $this->carts->restoreAfterFailedCheckout($order->cartId);
                if ($order->details()->couponCode !== null) {
                    $this->coupons->release($order->details()->couponCode);
                }
                $payment->declineWithoutProvider();
                $order->cancelAfterPaymentFailure();
                $this->payments->save($payment, 'card_declined');
                $this->orders->save($order);
                $this->payments->finishAttempt($attemptId, 'declined', null, 'declined');
            });

            throw $exception;
        } catch (PaymentGatewayTimeout $exception) {
            $this->transactions->run(function () use ($payment, $attemptId): void {
                $payment->retryAfterTimeout();
                $this->payments->save($payment, 'gateway_timeout');
                $this->payments->finishAttempt($attemptId, 'timeout', null, 'timeout');
            });

            throw $exception;
        } catch (Throwable $exception) {
            $this->transactions->run(function () use ($payment, $attemptId): void {
                $payment->retryAfterFailure('gateway_error');
                $this->payments->save($payment, 'gateway_error');
                $this->payments->finishAttempt($attemptId, 'failed', null, 'gateway_error');
            });

            throw $exception;
        }

        try {
            $this->transactions->run(function () use ($payment, $order, $attemptId, $result): void {
                if ($result->status === 'approved') {
                    $this->transitionReservations($payment, $order, true);
                    $payment->approve($result->transactionId);
                    $order->markPaid();
                    $attemptStatus = 'succeeded';
                } elseif ($result->status === 'declined') {
                    $this->transitionReservations($payment, $order, false);
                    $payment->decline($result->transactionId);
                    $order->cancelAfterPaymentFailure();
                    $attemptStatus = 'declined';
                } elseif ($result->status === 'pending') {
                    $payment->awaitProvider($result->transactionId);
                    $attemptStatus = 'succeeded';
                } else {
                    throw new DomainException('Unsupported payment gateway result.');
                }

                $this->payments->save($payment, 'gateway');
                $this->instructions->save($payment->id, $result);
                $this->orders->save($order);
                $this->payments->finishAttempt($attemptId, $attemptStatus, $result->transactionId, $result->status);
            });
        } catch (ReservationConflict $exception) {
            if ($result->status !== 'approved') {
                throw $exception;
            }

            $refundKey = Uuid::uuid5(Uuid::NAMESPACE_URL, 'stock-compensation:'.$payment->id)->toString();
            $this->gateway->refund($result->transactionId, $refundKey, $payment->amount);
            $this->transactions->run(function () use ($payment, $order, $attemptId, $result): void {
                $this->transitionReservations($payment, $order, false);
                $payment->decline($result->transactionId);
                $order->cancelAfterPaymentFailure();
                $this->payments->save($payment, 'stock_compensation');
                $this->orders->save($order);
                $this->payments->finishAttempt($attemptId, 'failed', $result->transactionId, 'stock_reservation_expired');
            });
        }

        return $payment;
    }

    /** @return array{Payment, string|null} */
    private function start(string $orderId): array
    {
        return $this->transactions->run(function () use ($orderId): array {
            $payment = $this->payments->findByOrder($orderId, true)
                ?? throw new DomainException('Payment intent not found.');
            if (in_array($payment->status(), [PaymentStatus::Paid, PaymentStatus::Declined], true)) {
                return [$payment, null];
            }
            if ($payment->status() === PaymentStatus::Pending && $payment->providerPaymentId() !== null) {
                return [$payment, null];
            }
            if ($payment->status() !== PaymentStatus::Pending) {
                throw new DomainException('Payment is already being processed.');
            }

            $payment->start();
            $this->payments->save($payment, 'processor');

            return [$payment, $this->payments->startAttempt($payment)];
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
