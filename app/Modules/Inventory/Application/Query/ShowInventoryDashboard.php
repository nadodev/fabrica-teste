<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Query;

use App\Modules\Inventory\Application\DTO\InventoryDashboard;
use App\Modules\Inventory\Application\Port\InventoryReadModel;

final readonly class ShowInventoryDashboard
{
    public function __construct(private InventoryReadModel $inventory) {}

    public function handle(): InventoryDashboard
    {
        return $this->inventory->dashboard();
    }
}
