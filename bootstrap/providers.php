<?php

use App\Modules\Catalog\Infrastructure\CatalogServiceProvider;
use App\Modules\Shared\Infrastructure\SharedServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    SharedServiceProvider::class,
];
