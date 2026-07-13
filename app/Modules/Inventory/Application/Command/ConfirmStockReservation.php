<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Command;

use App\Modules\Inventory\Application\Port\StockReservationLifecycle;

final readonly class ConfirmStockReservation
{
    public function __construct(private StockReservationLifecycle $reservations) {}

    public function handle(string $reservationId): void
    {
        $this->reservations->confirm($reservationId);
    }
}
