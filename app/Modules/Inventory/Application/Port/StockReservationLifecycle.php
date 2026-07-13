<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Port;

interface StockReservationLifecycle
{
    public function confirm(string $reservationId): void;

    public function release(string $reservationId): void;

    public function expireDue(int $limit): int;
}
