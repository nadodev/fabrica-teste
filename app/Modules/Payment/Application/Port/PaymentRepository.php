<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Port;

use App\Modules\Payment\Domain\Payment;

interface PaymentRepository
{
    public function findByOrder(string $orderId, bool $forUpdate = false): ?Payment;

    public function findByProviderId(string $providerPaymentId, bool $forUpdate = false): ?Payment;

    /** @return list<Payment> */
    public function forReconciliation(int $limit): array;

    public function save(Payment $payment, string $source): void;

    public function startAttempt(Payment $payment, string $operation = 'charge'): string;

    public function finishAttempt(string $attemptId, string $status, ?string $providerTransactionId = null, ?string $responseCode = null): void;
}
