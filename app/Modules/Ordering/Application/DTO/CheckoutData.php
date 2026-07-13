<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTO;

final readonly class CheckoutData
{
    /** @param array<string, mixed>|null $shippingQuote */
    public function __construct(
        public string $checkoutType,
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        public ?string $customerDocument,
        public string $shippingZip,
        public string $shippingAddress,
        public string $shippingNumber,
        public string $shippingCity,
        public string $shippingState,
        public string $deliveryMethod,
        public string $paymentMethod,
        public ?string $notes,
        public ?string $couponCode,
        public ?array $shippingQuote,
        public ?int $customerUserId = null,
    ) {}
}
