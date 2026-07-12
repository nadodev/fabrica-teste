<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Application\Port;

use App\Modules\Shipping\Application\DTO\ShippingOption;
use App\Modules\Shipping\Application\DTO\ShippingQuoteRequest;

interface ShippingQuoteGateway
{
    /** @return list<ShippingOption> */
    public function quote(ShippingQuoteRequest $request): array;
}
