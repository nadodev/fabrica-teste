<?php

declare(strict_types=1);

namespace App\Modules\Customers\Infrastructure;

use App\Modules\Customers\Application\Port\CustomerAccountRepository;
use App\Modules\Customers\Infrastructure\Persistence\DatabaseCustomerAccountRepository;
use Illuminate\Support\ServiceProvider;

final class CustomersServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CustomerAccountRepository::class => DatabaseCustomerAccountRepository::class,
    ];
}
