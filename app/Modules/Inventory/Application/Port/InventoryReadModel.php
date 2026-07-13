<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Port;

use App\Modules\Inventory\Application\DTO\InventoryDashboard;

interface InventoryReadModel
{
    public function dashboard(): InventoryDashboard;
}
