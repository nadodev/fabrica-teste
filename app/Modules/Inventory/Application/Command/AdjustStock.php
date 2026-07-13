<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Command;

use App\Modules\Inventory\Application\Port\StockManager;

final readonly class AdjustStock
{
    public function __construct(private StockManager $stock) {}

    public function handle(string $reference, string $stockId, int $quantity): void
    {
        $this->stock->adjust($reference, $stockId, $quantity);
    }
}
