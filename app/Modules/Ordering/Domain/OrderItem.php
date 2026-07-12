<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain;

use App\Modules\Shared\Domain\ValueObject\Money;

final readonly class OrderItem
{
    public function __construct(
        public string $productId,
        public string $sku,
        public string $name,
        public Money $unitPrice,
        public int $quantity,
    ) {}

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
