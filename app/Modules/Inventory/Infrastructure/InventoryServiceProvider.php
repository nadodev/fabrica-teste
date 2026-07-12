<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure;

use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Inventory\Application\Port\StockManager;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        StockGateway::class => DatabaseStockGateway::class,
        StockManager::class => DatabaseStockGateway::class,
    ];
}
