<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\DTO;

final readonly class InventoryDashboard
{
    /**
     * @param  list<array<string, int|string|null>>  $stocks
     * @param  list<array<string, int|string|null>>  $movements
     */
    public function __construct(public array $stocks, public array $movements) {}
}
