<?php

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

it('blocks checkout until a shipping option is selected', function () {
    [$token] = shippingRequirementCart(10000);

    $this->withSession(['cart_token' => $token])
        ->get(route('checkout'))
        ->assertRedirect(route('carrinho'))
        ->assertSessionHasErrors(['shipping' => 'Calcule e selecione uma opcao de frete antes de finalizar.']);
});

it('applies configured free shipping automatically and allows checkout', function () {
    configureFreeShipping('100.00', 4);
    [$token] = shippingRequirementCart(10000);

    $this->withSession(['cart_token' => $token])
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('checkout')
            ->where('cart.shipping.serviceId', 'free-shipping')
            ->where('cart.shipping.priceAmount', 0)
            ->where('cart.shipping.deliveryTime', 4));

    $this->withSession(['cart_token' => $token])->post(route('checkout.store'), [
        'customerName' => 'Cliente Frete Gratis',
        'customerEmail' => 'gratis@example.com',
        'customerPhone' => '11999999999',
        'shippingZip' => '01001000',
        'shippingAddress' => 'Rua Teste',
        'shippingNumber' => '10',
        'shippingCity' => 'Sao Paulo',
        'shippingState' => 'SP',
        'checkoutType' => 'quote',
        'deliveryMethod' => 'shipping',
        'paymentMethod' => 'combine',
        'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertRedirect();

    $this->assertDatabaseHas('ordering_orders', [
        'shipping_service' => 'Frete gratis',
        'shipping_amount' => 0,
        'delivery_method' => 'shipping',
    ]);
});

it('does not apply free shipping below the configured minimum', function () {
    configureFreeShipping('100.01');
    [$token] = shippingRequirementCart(10000);

    $this->withSession(['cart_token' => $token])
        ->get(route('checkout'))
        ->assertRedirect(route('carrinho'))
        ->assertSessionHasErrors('shipping');
});

it('does not trust a forged free shipping quote below the configured minimum', function () {
    configureFreeShipping('200.00');
    [$token] = shippingRequirementCart(10000);

    $this->from(route('checkout'))->withSession([
        'cart_token' => $token,
        'shipping_quote' => [
            'serviceId' => 'free-shipping', 'name' => 'Frete gratis', 'companyName' => 'Loja',
            'priceAmount' => 0, 'deliveryTime' => 0,
        ],
    ])->post(route('checkout.store'), [
        'customerName' => 'Cliente', 'customerEmail' => 'cliente@example.com', 'customerPhone' => '11999999999',
        'shippingZip' => '01001000', 'shippingAddress' => 'Rua Teste', 'shippingNumber' => '10',
        'shippingCity' => 'Sao Paulo', 'shippingState' => 'SP', 'checkoutType' => 'quote',
        'deliveryMethod' => 'shipping', 'paymentMethod' => 'combine', 'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('checkout');

    $this->assertDatabaseCount('ordering_orders', 0);
});

it('rejects pickup as a delivery method for new checkouts', function () {
    $this->from(route('checkout'))->post(route('checkout.store'), [
        'customerName' => 'Cliente',
        'customerEmail' => 'cliente@example.com',
        'customerPhone' => '11999999999',
        'shippingZip' => '01001000',
        'shippingAddress' => 'Rua Teste',
        'shippingNumber' => '10',
        'shippingCity' => 'Sao Paulo',
        'shippingState' => 'SP',
        'checkoutType' => 'quote',
        'deliveryMethod' => 'pickup',
        'paymentMethod' => 'combine',
        'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('deliveryMethod');
});

/** @return array{string, string} */
function shippingRequirementCart(int $price): array
{
    $productId = (string) Str::uuid();
    $sku = 'SHIPPING-'.Str::upper(Str::random(8));
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => $sku,
        'name' => 'Produto com entrega',
        'description' => '',
        'price_amount' => $price,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('stock-'.$productId, $productId, 2);

    $token = 'cart-'.Str::uuid();
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto com entrega', new Money($price), 1, $sku);
    app(CartRepository::class)->save($cart);

    return [$token, $productId];
}

function configureFreeShipping(string $minimum, int $estimatedDays = 0): void
{
    DB::table('shipping_settings')->where('id', 1)->update([
        'options' => json_encode([
            'freeShippingEnabled' => true,
            'freeShippingMinimum' => $minimum,
            'estimatedDays' => $estimatedDays,
        ], JSON_THROW_ON_ERROR),
        'updated_at' => now(),
    ]);
}
