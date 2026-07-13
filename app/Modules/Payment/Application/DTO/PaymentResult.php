<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class PaymentResult
{
    public function __construct(
        public string $transactionId,
        public string $status,
        public ?string $redirectUrl = null,
        public ?string $pixPayload = null,
        public ?string $pixEncodedImage = null,
        public ?string $pixExpirationDate = null,
    ) {}
}
