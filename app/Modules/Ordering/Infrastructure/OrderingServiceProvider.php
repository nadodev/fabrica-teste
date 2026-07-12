<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure;

use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Ordering\Infrastructure\Persistence\DatabaseOrderRepository;
use Illuminate\Support\ServiceProvider;

final class OrderingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [OrderRepository::class => DatabaseOrderRepository::class];
}
