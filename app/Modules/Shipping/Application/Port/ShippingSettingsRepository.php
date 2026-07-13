<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Application\Port;

interface ShippingSettingsRepository
{
    /** @return array{freeShippingEnabled: bool, freeShippingMinimum: string, estimatedDays: int} */
    public function configuration(): array;
}
