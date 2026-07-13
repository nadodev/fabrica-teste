<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Gateway;

use App\Modules\Payment\Application\DTO\PaymentRequest;
use App\Modules\Payment\Application\DTO\PaymentResult;
use App\Modules\Payment\Application\Exception\PaymentGatewayTimeout;
use App\Modules\Payment\Application\Port\PaymentGateway;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final readonly class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private ConnectionInterface $database) {}

    public function charge(PaymentRequest $request): PaymentResult
    {
        $outcome = (string) config('payment.fake_outcome', 'approved');
        if (! in_array($outcome, ['approved', 'declined', 'timeout'], true)) {
            throw new RuntimeException('Invalid fake payment outcome.');
        }

        $transactionId = 'fake_'.substr(hash('sha256', $request->idempotencyKey), 0, 32);
        $existing = $this->database->table('payment_fake_transactions')->where('idempotency_key', $request->idempotencyKey)->first();
        if ($existing === null) {
            $this->database->table('payment_fake_transactions')->insert([
                'idempotency_key' => $request->idempotencyKey,
                'provider_transaction_id' => $transactionId,
                'order_id' => $request->orderId,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'outcome' => $outcome,
                'refunded_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ((string) $existing->order_id !== $request->orderId || (int) $existing->amount !== $request->amount || (string) $existing->currency !== $request->currency) {
            throw new RuntimeException('Fake gateway idempotency key was reused with different data.');
        } else {
            $outcome = (string) $existing->outcome;
            $transactionId = (string) $existing->provider_transaction_id;
        }

        if ($outcome === 'timeout') {
            throw new PaymentGatewayTimeout('Fake gateway timed out.');
        }

        return new PaymentResult($transactionId, $outcome);
    }

    public function refund(string $transactionId, string $idempotencyKey, ?int $amount = null): PaymentResult
    {
        $existingRefund = $this->database->table('payment_fake_refunds')->where('idempotency_key', $idempotencyKey)->first();
        if ($existingRefund !== null) {
            if ((string) $existingRefund->provider_transaction_id !== $transactionId || ($amount !== null && (int) $existingRefund->amount !== $amount)) {
                throw new RuntimeException('Fake refund idempotency key was reused with different data.');
            }

            return new PaymentResult($transactionId, 'refunded');
        }

        $transaction = $this->database->table('payment_fake_transactions')->where('provider_transaction_id', $transactionId)->first();
        if ($transaction === null || $transaction->outcome !== 'approved') {
            throw new RuntimeException('Fake transaction cannot be refunded.');
        }

        $refund = $amount ?? (int) $transaction->amount;
        if ($refund < 1 || (int) $transaction->refunded_amount + $refund > (int) $transaction->amount) {
            throw new RuntimeException('Invalid fake refund amount.');
        }
        $this->database->transaction(function () use ($idempotencyKey, $transactionId, $refund): void {
            $this->database->table('payment_fake_refunds')->insert([
                'idempotency_key' => $idempotencyKey,
                'provider_transaction_id' => $transactionId,
                'amount' => $refund,
                'created_at' => now(),
            ]);
            $this->database->table('payment_fake_transactions')->where('provider_transaction_id', $transactionId)->increment('refunded_amount', $refund, ['updated_at' => now()]);
        });

        return new PaymentResult($transactionId, 'refunded');
    }
}
