<?php

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;
use App\Modules\Ordering\Application\Command\CheckoutCart;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Shared\Domain\ValueObject\Money;

it('atomically snapshots a cart, reserves stock and creates one order', function () {
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83460';
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'CHECKOUT-001',
        'name' => 'Produto do checkout',
        'description' => '',
        'price_amount' => 12990,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('checkout-stock-001', $productId, 3);

    $token = 'secure-cart-token';
    $cart = new Cart('0190f566-c399-79e3-a553-7e5fb8d83461', hash('sha256', $token));
    $cart->add($productId, 'Produto do checkout', new Money(12990), 2, 'CHECKOUT-001');
    app(CartRepository::class)->save($cart);

    $checkout = app(CheckoutCart::class);
    $order = $checkout->handle('0190f566-c399-79e3-a553-7e5fb8d83462', $token);
    $replayed = $checkout->handle('0190f566-c399-79e3-a553-7e5fb8d83463', $token);

    expect($order->status())->toBe(OrderStatus::AwaitingPayment)
        ->and($order->total()->amount)->toBe(25980)
        ->and($replayed->id)->toBe($order->id);
    $this->assertDatabaseCount('ordering_orders', 1);
    $this->assertDatabaseHas('inventory_stock', ['product_id' => $productId, 'on_hand' => 3, 'reserved' => 2]);
    $this->assertDatabaseHas('cart_carts', ['id' => $cart->id, 'status' => 'converted']);
});
