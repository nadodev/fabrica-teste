<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Port;

interface StockGateway
{
    public function available(string $productId): int;

    public function reserve(string $reservationId, string $productId, int $quantity): void;

    public function release(string $reservationId): void;
}
