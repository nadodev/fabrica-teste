<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Port;

use App\Modules\Inventory\Application\DTO\StockLevel;

interface StockGateway
{
    public function tracked(string $productId, ?string $variationKey = null): bool;

    public function available(string $productId, ?string $variationKey = null): int;

    /** @return list<StockLevel> */
    public function levels(string $productId): array;

    public function reserve(string $reservationId, string $productId, int $quantity, ?string $variationKey = null): void;
}
