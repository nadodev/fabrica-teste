<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Application\Port\CustomerIdentityRepository;
use App\Modules\Identity\Application\Port\CustomerNotificationSender;
use App\Modules\Identity\Application\Port\PasswordResetter;
use App\Modules\Identity\Infrastructure\Notification\LaravelCustomerNotificationSender;
use App\Modules\Identity\Infrastructure\Password\LaravelPasswordResetter;
use App\Modules\Identity\Infrastructure\Persistence\EloquentCustomerIdentityRepository;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CustomerIdentityRepository::class => EloquentCustomerIdentityRepository::class,
        CustomerNotificationSender::class => LaravelCustomerNotificationSender::class,
        PasswordResetter::class => LaravelPasswordResetter::class,
    ];
}
