<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Payment\Application\DTO\ProviderPaymentSnapshot;
use App\Modules\Payment\Application\DTO\ProviderWebhookEvent;
use App\Modules\Payment\Application\Port\PaymentReconciliationGateway;
use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Application\Port\PaymentWebhookInbox;
use App\Modules\Payment\Domain\Payment;
use Throwable;

final readonly class ReconcileAsaasPayments
{
    public function __construct(
        private PaymentRepository $payments,
        private PaymentReconciliationGateway $gateway,
        private PaymentWebhookInbox $inbox,
        private ProcessAsaasWebhooks $processor,
    ) {}

    public function handle(int $limit = 100): int
    {
        if (config('payment.gateway') !== 'asaas' || ! (bool) config('payment.asaas.live_enabled')) {
            return 0;
        }

        $received = 0;
        foreach ($this->payments->forReconciliation($limit) as $payment) {
            try {
                $snapshot = $this->gateway->fetch((string) $payment->providerPaymentId());
                $event = $this->event($payment, $snapshot);
                if ($event === null) {
                    continue;
                }
                $this->inbox->receive($event);
                $received++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($received === 0) {
            return 0;
        }

        return $this->processor->handle(max($received, $limit));
    }

    private function event(Payment $payment, ProviderPaymentSnapshot $snapshot): ?ProviderWebhookEvent
    {
        $event = $this->eventName($payment, $snapshot);
        if ($event === null) {
            return null;
        }
        $payload = [
            'id' => $snapshot->providerPaymentId,
            'status' => $snapshot->status,
            'billingType' => $snapshot->billingType,
            'refundedValue' => $snapshot->refundedAmount / 100,
            'chargebackStatus' => $snapshot->chargebackStatus,
            'chargebackReason' => $snapshot->chargebackReason,
        ];
        $fingerprint = hash('sha256', json_encode([$event, $payload], JSON_THROW_ON_ERROR));

        return new ProviderWebhookEvent(
            'reconciliation:'.substr($fingerprint, 0, 64),
            $event,
            $snapshot->providerPaymentId,
            $payload,
        );
    }

    private function eventName(Payment $payment, ProviderPaymentSnapshot $snapshot): ?string
    {
        if ($snapshot->chargebackStatus !== null || str_starts_with($snapshot->status, 'CHARGEBACK')) {
            return match ($snapshot->status) {
                'CHARGEBACK_REQUESTED' => 'PAYMENT_CHARGEBACK_REQUESTED',
                'AWAITING_CHARGEBACK_REVERSAL' => 'PAYMENT_AWAITING_CHARGEBACK_REVERSAL',
                default => 'PAYMENT_CHARGEBACK_DISPUTE',
            };
        }
        if ($snapshot->refundedAmount >= $payment->amount || $snapshot->status === 'REFUNDED') {
            return 'PAYMENT_REFUNDED';
        }
        if ($snapshot->refundedAmount > 0) {
            return 'PAYMENT_PARTIALLY_REFUNDED';
        }

        return match ($snapshot->status) {
            'RECEIVED', 'RECEIVED_IN_CASH' => 'PAYMENT_RECEIVED',
            'CONFIRMED' => 'PAYMENT_CONFIRMED',
            'CREDIT_CARD_REFUSED' => 'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED',
            'DELETED' => 'PAYMENT_DELETED',
            'REFUND_REQUESTED' => 'PAYMENT_REFUND_IN_PROGRESS',
            default => null,
        };
    }
}
