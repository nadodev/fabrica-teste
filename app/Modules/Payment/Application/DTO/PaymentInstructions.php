<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class PaymentInstructions
{
    public function __construct(
        public ?string $paymentUrl,
        public ?string $pixPayload,
        public ?string $pixEncodedImage,
        public ?string $pixExpirationDate,
    ) {}
}
