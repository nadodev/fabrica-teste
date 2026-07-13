<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class CreditCardData
{
    public function __construct(
        public string $holderName,
        public string $number,
        public string $expiryMonth,
        public string $expiryYear,
        public string $ccv,
        public string $remoteIp,
    ) {}
}
