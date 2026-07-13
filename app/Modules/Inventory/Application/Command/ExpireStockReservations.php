<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Command;

use App\Modules\Inventory\Application\Port\StockReservationLifecycle;
use InvalidArgumentException;

final readonly class ExpireStockReservations
{
    public function __construct(private StockReservationLifecycle $reservations) {}

    public function handle(int $limit = 100): int
    {
        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Expiration batch limit must be between 1 and 1000.');
        }

        return $this->reservations->expireDue($limit);
    }
}
