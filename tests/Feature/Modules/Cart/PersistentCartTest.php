<?php

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Exception\CartConcurrencyConflict;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Shared\Domain\ValueObject\Money;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();

    ProductRecord::query()->create([
        'id' => '0190f566-c399-79e3-a553-7e5fb8d83440',
        'sku' => 'CART-001',
        'name' => 'Camisa do carrinho',
        'description' => '',
        'price_amount' => 7990,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
});

it('persists a server-priced anonymous cart and safely replays the command', function () {
    $payload = ['productId' => '0190f566-c399-79e3-a553-7e5fb8d83440', 'quantity' => 2];
    $headers = ['Idempotency-Key' => 'cart-add-001'];

    $this->post(route('carrinho.itens.store'), $payload, $headers)->assertRedirect(route('carrinho'));
    $this->post(route('carrinho.itens.store'), $payload, $headers)->assertStatus(302);

    $this->assertDatabaseCount('cart_carts', 1);
    $this->assertDatabaseHas('cart_items', [
        'product_id' => $payload['productId'],
        'quantity' => 2,
        'unit_price_amount' => 7990,
    ]);

    $this->get(route('carrinho'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('carrinho')
        ->where('cart.totalAmount', 15980)
        ->where('cart.items.0.sku', 'CART-001'));

    $this->delete(route('carrinho.itens.destroy', $payload['productId']), [], ['Idempotency-Key' => 'cart-remove-001'])
        ->assertRedirect(route('carrinho'));
    $this->assertDatabaseCount('cart_items', 0);
});

it('detects optimistic concurrency conflicts', function () {
    $repository = app(CartRepository::class);
    $cart = new Cart('0190f566-c399-79e3-a553-7e5fb8d83441', hash('sha256', 'token'));
    $cart->add('0190f566-c399-79e3-a553-7e5fb8d83440', 'Camisa', new Money(7990));
    $repository->save($cart);

    $firstCopy = $repository->find($cart->id);
    $secondCopy = $repository->find($cart->id);
    $firstCopy?->add('0190f566-c399-79e3-a553-7e5fb8d83440', 'Camisa', new Money(7990));
    $repository->save($firstCopy);
    $repository->save($secondCopy);
})->throws(CartConcurrencyConflict::class);
