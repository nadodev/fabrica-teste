<?php

use App\Modules\Cart\Infrastructure\CartServiceProvider;
use App\Modules\Catalog\Infrastructure\CatalogServiceProvider;
use App\Modules\Customers\Infrastructure\CustomersServiceProvider;
use App\Modules\Inventory\Infrastructure\InventoryServiceProvider;
use App\Modules\Ordering\Infrastructure\OrderingServiceProvider;
use App\Modules\Payment\Infrastructure\PaymentServiceProvider;
use App\Modules\Shared\Infrastructure\SharedServiceProvider;
use App\Modules\Shipping\Infrastructure\ShippingServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    CartServiceProvider::class,
    CustomersServiceProvider::class,
    InventoryServiceProvider::class,
    OrderingServiceProvider::class,
    PaymentServiceProvider::class,
    ShippingServiceProvider::class,
    SharedServiceProvider::class,
];
