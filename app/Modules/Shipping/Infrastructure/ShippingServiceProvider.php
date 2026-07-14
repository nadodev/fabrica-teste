<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Infrastructure;

use App\Modules\Shipping\Application\Port\ShippingQuoteGateway;
use App\Modules\Shipping\Application\Port\ShippingSettingsRepository;
use App\Modules\Shipping\Infrastructure\Persistence\DatabaseShippingSettingsRepository;
use App\Support\MelhorEnvioClient;
use Illuminate\Support\ServiceProvider;

final class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShippingSettingsRepository::class, DatabaseShippingSettingsRepository::class);
        $this->app->bind(ShippingQuoteGateway::class, MelhorEnvioClient::class);
    }
}
