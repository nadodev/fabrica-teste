<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Shipping\Application\DTO\ShippingOption;
use App\Modules\Shipping\Application\DTO\ShippingQuoteRequest;
use App\Modules\Shipping\Application\Port\ShippingQuoteGateway;

final class FakeShippingQuoteGateway implements ShippingQuoteGateway
{
    /** @return list<ShippingOption> */
    public function quote(ShippingQuoteRequest $request): array
    {
        return [
            new ShippingOption('test-shipping', 'Entrega teste', 'Transportadora teste', 0, 'BRL', 2),
            new ShippingOption('customer-address-shipping', 'Entrega teste', 'Transportadora teste', 0, 'BRL', 2),
            new ShippingOption('pac-test', 'PAC', 'Correios', 1500, 'BRL', 5),
        ];
    }
}
