<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain;

use DomainException;

final class Payment
{
    public function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $method,
        public readonly string $idempotencyKey,
        public readonly bool $stockReserved,
        private PaymentStatus $status = PaymentStatus::Pending,
        private ?string $providerPaymentId = null,
        private ?string $failureCode = null,
        private int $version = 0,
        private int $refundedAmount = 0,
    ) {
        if ($amount < 1 || mb_strlen($currency) !== 3) {
            throw new DomainException('Payment must have a positive amount and valid currency.');
        }
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function providerPaymentId(): ?string
    {
        return $this->providerPaymentId;
    }

    public function failureCode(): ?string
    {
        return $this->failureCode;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function refundedAmount(): int
    {
        return $this->refundedAmount;
    }

    public function start(): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new DomainException('Only a pending payment can be processed.');
        }

        $this->status = PaymentStatus::Processing;
        $this->version++;
    }

    public function approve(string $providerPaymentId): void
    {
        if ($this->status !== PaymentStatus::Processing) {
            throw new DomainException('Only a processing payment can be approved.');
        }

        $this->status = PaymentStatus::Paid;
        $this->providerPaymentId = $providerPaymentId;
        $this->failureCode = null;
        $this->version++;
    }

    public function decline(string $providerPaymentId): void
    {
        if ($this->status !== PaymentStatus::Processing) {
            throw new DomainException('Only a processing payment can be declined.');
        }

        $this->status = PaymentStatus::Declined;
        $this->providerPaymentId = $providerPaymentId;
        $this->failureCode = 'declined';
        $this->version++;
    }

    public function retryAfterTimeout(): void
    {
        if ($this->status !== PaymentStatus::Processing) {
            throw new DomainException('Only a processing payment can return to pending.');
        }

        $this->status = PaymentStatus::Pending;
        $this->failureCode = 'timeout';
        $this->version++;
    }

    public function awaitProvider(string $providerPaymentId): void
    {
        if ($this->status !== PaymentStatus::Processing) {
            throw new DomainException('Only a processing payment can await provider confirmation.');
        }

        $this->status = PaymentStatus::Pending;
        $this->providerPaymentId = $providerPaymentId;
        $this->failureCode = null;
        $this->version++;
    }

    public function refund(): void
    {
        if (! in_array($this->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true) || $this->providerPaymentId === null) {
            throw new DomainException('Only a paid payment can be refunded.');
        }

        $this->status = PaymentStatus::Refunded;
        $this->refundedAmount = $this->amount;
        $this->failureCode = null;
        $this->version++;
    }

    public function approveFromProvider(string $providerPaymentId): void
    {
        if ($this->status === PaymentStatus::Paid) {
            return;
        }
        if (! in_array($this->status, [PaymentStatus::Pending, PaymentStatus::Processing, PaymentStatus::Chargeback], true)) {
            throw new DomainException('Payment cannot be approved from its current state.');
        }

        $this->status = PaymentStatus::Paid;
        $this->providerPaymentId = $providerPaymentId;
        $this->failureCode = null;
        $this->version++;
    }

    public function partiallyRefund(int $amount): void
    {
        if (! in_array($this->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)
            || $amount <= $this->refundedAmount
            || $amount >= $this->amount) {
            throw new DomainException('Invalid partial refund.');
        }

        $this->status = PaymentStatus::PartiallyRefunded;
        $this->refundedAmount = $amount;
        $this->version++;
    }

    public function markChargeback(string $code): void
    {
        if ($this->status === PaymentStatus::Chargeback && $this->failureCode === $code) {
            return;
        }
        if (! in_array($this->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded, PaymentStatus::Chargeback], true)) {
            throw new DomainException('Only a paid payment can enter chargeback.');
        }

        $this->status = PaymentStatus::Chargeback;
        $this->failureCode = $code;
        $this->version++;
    }

    public function declineFromProvider(string $providerPaymentId, string $code): void
    {
        if ($this->status === PaymentStatus::Declined) {
            return;
        }
        if (! in_array($this->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
            throw new DomainException('Payment cannot be declined from its current state.');
        }

        $this->status = PaymentStatus::Declined;
        $this->providerPaymentId = $providerPaymentId;
        $this->failureCode = $code;
        $this->version++;
    }
}
