<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class CheckoutSuccessView
{
    public function __construct(
        public string $orderNumber,
        public string $checkoutType,
        public string $paymentMethod,
        public string $paymentStatus,
        public ?PaymentInstructions $instructions,
    ) {}
}
