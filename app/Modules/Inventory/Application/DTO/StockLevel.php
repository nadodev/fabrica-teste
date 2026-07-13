<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\DTO;

final readonly class StockLevel
{
    public function __construct(
        public string $id,
        public string $productId,
        public ?string $variationKey,
        public string $sku,
        public int $onHand,
        public int $reserved,
        public int $lowStockThreshold,
    ) {}

    public function available(): int
    {
        return max(0, $this->onHand - $this->reserved);
    }
}
