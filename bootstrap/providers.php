<?php

use App\Modules\Cart\Infrastructure\CartServiceProvider;
use App\Modules\Catalog\Infrastructure\CatalogServiceProvider;
use App\Modules\Inventory\Infrastructure\InventoryServiceProvider;
use App\Modules\Shared\Infrastructure\SharedServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    CartServiceProvider::class,
    InventoryServiceProvider::class,
    SharedServiceProvider::class,
];
