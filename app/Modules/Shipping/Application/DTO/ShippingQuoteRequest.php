<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Application\DTO;

final readonly class ShippingQuoteRequest
{
    /** @param list<array{productId: string, cartItemKey: string, quantity: int, unitPriceAmount: int, weightInGrams: int, widthInCentimeters: int, heightInCentimeters: int, lengthInCentimeters: int}> $items */
    public function __construct(public string $postalCode, public array $items) {}
}
