<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Port;

interface StockManager
{
    public function receive(string $reference, string $productId, int $quantity, ?string $variationKey = null): void;

    public function adjust(string $reference, string $stockId, int $quantity): void;

    /** @param list<array{id?: string, sku?: string, stock?: int, lowStockThreshold?: int}> $variations */
    public function synchronizeProduct(string $reference, string $productId, string $baseSku, int $simpleQuantity, array $variations): void;
}
