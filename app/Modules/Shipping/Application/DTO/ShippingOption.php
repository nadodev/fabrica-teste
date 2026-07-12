<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Application\DTO;

final readonly class ShippingOption
{
    public function __construct(public string $serviceCode, public string $name, public int $priceAmount, public string $currency, public int $estimatedDays) {}
}
