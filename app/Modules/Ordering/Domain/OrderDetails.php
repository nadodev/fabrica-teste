<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain;

use App\Modules\Shared\Domain\ValueObject\Money;
use DomainException;

final readonly class OrderDetails
{
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
        public ?string $shippingService,
        public ?string $shippingCompany,
        public Money $shipping,
        public ?int $shippingDeliveryTime,
        public string $paymentMethod,
        public string $paymentStatus,
        public ?string $notes,
        public ?string $couponCode,
        public Money $discount,
    ) {
        if (! in_array($checkoutType, ['quote', 'payment'], true)) {
            throw new DomainException('Invalid checkout type.');
        }

        if (! in_array($deliveryMethod, ['shipping', 'pickup'], true)) {
            throw new DomainException('Invalid delivery method.');
        }

        if ($deliveryMethod === 'pickup' && $shipping->amount !== 0) {
            throw new DomainException('Pickup cannot have a shipping charge.');
        }

        if ($discount->currency !== $shipping->currency) {
            throw new DomainException('Order monetary values must use the same currency.');
        }
    }

    public function withPaymentStatus(string $paymentStatus): self
    {
        return new self(
            $this->checkoutType,
            $this->customerName,
            $this->customerEmail,
            $this->customerPhone,
            $this->customerDocument,
            $this->shippingZip,
            $this->shippingAddress,
            $this->shippingNumber,
            $this->shippingCity,
            $this->shippingState,
            $this->deliveryMethod,
            $this->shippingService,
            $this->shippingCompany,
            $this->shipping,
            $this->shippingDeliveryTime,
            $this->paymentMethod,
            $paymentStatus,
            $this->notes,
            $this->couponCode,
            $this->discount,
        );
    }
}
