<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentStatus;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;

final readonly class DatabasePaymentRepository implements PaymentRepository
{
    public function __construct(private ConnectionInterface $database) {}

    public function findByOrder(string $orderId, bool $forUpdate = false): ?Payment
    {
        $query = $this->database->table('payment_payments')->where('order_id', $orderId);
        $record = $forUpdate ? $query->lockForUpdate()->first() : $query->first();

        return $record === null ? null : new Payment(
            (string) $record->id,
            (string) $record->order_id,
            (int) $record->amount,
            (string) $record->currency,
            (string) $record->method,
            (string) $record->idempotency_key,
            (bool) $record->stock_reserved,
            PaymentStatus::from((string) $record->status),
            $record->provider_payment_id === null ? null : (string) $record->provider_payment_id,
            $record->failure_code === null ? null : (string) $record->failure_code,
            (int) $record->version,
            (int) ($record->refunded_amount ?? 0),
        );
    }

    public function findByProviderId(string $providerPaymentId, bool $forUpdate = false): ?Payment
    {
        $query = $this->database->table('payment_payments')->where('provider_payment_id', $providerPaymentId);
        $record = $forUpdate ? $query->lockForUpdate()->first() : $query->first();

        return $record === null ? null : $this->hydrate((array) $record);
    }

    public function save(Payment $payment, string $source): void
    {
        $existing = $this->database->table('payment_payments')->where('id', $payment->id)->first();
        $from = $existing === null ? null : (string) $existing->status;
        $values = [
            'order_id' => $payment->orderId,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'method' => $payment->method,
            'status' => $payment->status()->value,
            'provider' => $existing === null ? (string) config('payment.gateway', 'fake') : (string) $existing->provider,
            'provider_payment_id' => $payment->providerPaymentId(),
            'idempotency_key' => $payment->idempotencyKey,
            'stock_reserved' => $payment->stockReserved,
            'failure_code' => $payment->failureCode(),
            'version' => $payment->version(),
            'refunded_amount' => $payment->refundedAmount(),
            'updated_at' => now(),
        ];
        if ($existing === null) {
            $values['id'] = $payment->id;
            $values['created_at'] = now();
            $this->database->table('payment_payments')->insert($values);
        } else {
            $this->database->table('payment_payments')->where('id', $payment->id)->update($values);
        }

        if ($from !== $payment->status()->value) {
            $this->database->table('payment_status_history')->insert([
                'payment_id' => $payment->id,
                'from_status' => $from,
                'to_status' => $payment->status()->value,
                'source' => $source,
                'created_at' => now(),
            ]);
        }
    }

    /** @param array<string, mixed> $record */
    private function hydrate(array $record): Payment
    {
        return new Payment(
            (string) $record['id'],
            (string) $record['order_id'],
            (int) $record['amount'],
            (string) $record['currency'],
            (string) $record['method'],
            (string) $record['idempotency_key'],
            (bool) $record['stock_reserved'],
            PaymentStatus::from((string) $record['status']),
            $record['provider_payment_id'] === null ? null : (string) $record['provider_payment_id'],
            $record['failure_code'] === null ? null : (string) $record['failure_code'],
            (int) $record['version'],
            (int) ($record['refunded_amount'] ?? 0),
        );
    }

    public function forReconciliation(int $limit): array
    {
        $payments = $this->database->table('payment_payments')
            ->where('provider', 'asaas')
            ->whereNotNull('provider_payment_id')
            ->whereNotIn('status', [PaymentStatus::Declined->value, PaymentStatus::Refunded->value, PaymentStatus::Cancelled->value])
            ->orderBy('updated_at')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(fn (object $record): Payment => $this->hydrate((array) $record))
            ->all();

        return array_values($payments);
    }

    public function startAttempt(Payment $payment, string $operation = 'charge'): string
    {
        $number = (int) $this->database->table('payment_attempts')->where('payment_id', $payment->id)->where('operation', $operation)->max('attempt_number') + 1;
        $id = Uuid::uuid5(Uuid::NAMESPACE_URL, $payment->id.':'.$operation.':'.$number)->toString();
        $this->database->table('payment_attempts')->insert([
            'id' => $id,
            'payment_id' => $payment->id,
            'attempt_number' => $number,
            'operation' => $operation,
            'status' => 'started',
            'started_at' => now(),
        ]);

        return $id;
    }

    public function finishAttempt(string $attemptId, string $status, ?string $providerTransactionId = null, ?string $responseCode = null): void
    {
        $this->database->table('payment_attempts')->where('id', $attemptId)->update([
            'status' => $status,
            'provider_transaction_id' => $providerTransactionId,
            'response_code' => $responseCode,
            'completed_at' => now(),
        ]);
    }
}
