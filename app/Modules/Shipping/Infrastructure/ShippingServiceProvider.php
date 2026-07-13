<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Infrastructure;

use App\Modules\Shipping\Application\Port\ShippingSettingsRepository;
use App\Modules\Shipping\Infrastructure\Persistence\DatabaseShippingSettingsRepository;
use Illuminate\Support\ServiceProvider;

final class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShippingSettingsRepository::class, DatabaseShippingSettingsRepository::class);
    }
}
