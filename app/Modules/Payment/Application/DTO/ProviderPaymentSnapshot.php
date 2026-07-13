<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class ProviderPaymentSnapshot
{
    public function __construct(
        public string $providerPaymentId,
        public string $status,
        public string $billingType,
        public int $refundedAmount,
        public ?string $chargebackStatus = null,
        public ?string $chargebackReason = null,
    ) {}
}
