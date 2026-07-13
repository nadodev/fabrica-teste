<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure;

use App\Modules\Ordering\Application\Port\CouponGateway;
use App\Modules\Ordering\Application\Port\CustomerOrderReadModel;
use App\Modules\Ordering\Application\Port\OrderNotificationGateway;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Ordering\Infrastructure\Notification\MailOrderNotificationGateway;
use App\Modules\Ordering\Infrastructure\Persistence\DatabaseCustomerOrderReadModel;
use App\Modules\Ordering\Infrastructure\Persistence\DatabaseOrderRepository;
use App\Modules\Ordering\Infrastructure\Promotion\DatabaseCouponGateway;
use Illuminate\Support\ServiceProvider;

final class OrderingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        OrderRepository::class => DatabaseOrderRepository::class,
        CouponGateway::class => DatabaseCouponGateway::class,
        OrderNotificationGateway::class => MailOrderNotificationGateway::class,
        CustomerOrderReadModel::class => DatabaseCustomerOrderReadModel::class,
    ];
}
