<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Port;

interface StockManager
{
    public function receive(string $reference, string $productId, int $quantity): void;
}
