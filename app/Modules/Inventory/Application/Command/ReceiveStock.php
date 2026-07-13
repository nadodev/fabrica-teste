<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Command;

use App\Modules\Inventory\Application\Port\StockManager;

final readonly class ReceiveStock
{
    public function __construct(private StockManager $stock) {}

    public function handle(string $reference, string $productId, int $quantity, ?string $variationKey = null): void
    {
        $this->stock->receive($reference, $productId, $quantity, $variationKey);
    }
}
