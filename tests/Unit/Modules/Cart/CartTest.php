<?php

use App\Modules\Cart\Domain\Cart;
use App\Modules\Shared\Domain\ValueObject\Money;

it('combines repeated products and calculates the cart total', function () {
    $cart = new Cart('cart-1');
    $cart->add('product-1', 'Camisa Polo', new Money(7990), 2);
    $cart->add('product-1', 'Camisa Polo', new Money(7990));
    expect($cart->items())->toHaveCount(1)
        ->and($cart->items()[0]->quantity)->toBe(3)
        ->and($cart->total()->amount)->toBe(23970);
});
