<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

it('lists only orders explicitly owned by the authenticated user', function () {
    $user = User::factory()->create(['email' => 'cliente@example.com']);
    $other = User::factory()->create(['email' => 'outro@example.com']);
    $owned = insertCustomerOrder($user->id, 'cliente@example.com', 'PED-OWNED');
    insertCustomerOrder(null, 'cliente@example.com', 'PED-SAME-EMAIL');
    insertCustomerOrder($other->id, 'outro@example.com', 'PED-OTHER');

    $this->actingAs($user)->get(route('cliente.conta'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('cliente/conta')
            ->has('orders', 1)
            ->where('orders.0.id', $owned)
            ->where('orders.0.number', 'PED-OWNED'));
});

it('requires authentication before exposing the customer order list', function () {
    $this->get(route('cliente.conta'))->assertRedirect(route('cliente.login'));
});

it('shows the complete order only to its authenticated owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $orderId = insertCustomerOrder($owner->id, $owner->email, 'PED-DETAIL');

    $this->actingAs($owner)->get(route('cliente.orders.show', $orderId))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('cliente/pedido')
            ->where('order.id', $orderId)
            ->where('order.number', 'PED-DETAIL')
            ->where('order.customerEmail', $owner->email)
            ->where('order.shippingService', 'PAC')
            ->where('order.totalAmount', 1250)
            ->has('order.items', 1)
            ->where('order.items.0.sku', 'SKU-DETAIL')
            ->where('order.items.0.quantity', 1));

    $this->actingAs($other)->get(route('cliente.orders.show', $orderId))->assertNotFound();
});

it('requires a verified authenticated account before exposing an order detail', function () {
    $owner = User::factory()->unverified()->create();
    $orderId = insertCustomerOrder($owner->id, $owner->email, 'PED-VERIFY');

    $this->get(route('cliente.orders.show', $orderId))->assertRedirect(route('cliente.login'));
    $this->actingAs($owner)->get(route('cliente.orders.show', $orderId))->assertRedirect(route('verification.notice'));
});

function insertCustomerOrder(?int $userId, string $email, string $number): string
{
    $cartId = (string) Str::uuid();
    $orderId = (string) Str::uuid();
    DB::table('cart_carts')->insert([
        'id' => $cartId,
        'token_hash' => hash('sha256', $cartId),
        'user_id' => $userId,
        'currency' => 'BRL',
        'status' => 'converted',
        'version' => 1,
        'expires_at' => now()->addDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('ordering_orders')->insert([
        'id' => $orderId,
        'number' => $number,
        'cart_id' => $cartId,
        'customer_user_id' => $userId,
        'status' => 'awaiting_payment',
        'checkout_type' => 'payment',
        'customer_name' => 'Cliente Teste',
        'customer_email' => $email,
        'customer_phone' => '11999999999',
        'shipping_zip' => '01001000',
        'shipping_address' => 'Praça da Sé',
        'shipping_number' => '100',
        'shipping_city' => 'São Paulo',
        'shipping_state' => 'SP',
        'delivery_method' => 'shipping',
        'shipping_service' => 'PAC',
        'shipping_company' => 'Correios',
        'shipping_amount' => 250,
        'shipping_delivery_time' => 5,
        'payment_method' => 'credit_card',
        'payment_status' => 'pending',
        'subtotal_amount' => 1000,
        'discount_amount' => 0,
        'total_amount' => 1250,
        'currency' => 'BRL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('ordering_order_items')->insert([
        'order_id' => $orderId,
        'product_id' => (string) Str::uuid(),
        'sku' => 'SKU-DETAIL',
        'name' => 'Camisa profissional',
        'unit_price_amount' => 1000,
        'price_currency' => 'BRL',
        'quantity' => 1,
        'subtotal_amount' => 1000,
    ]);

    return $orderId;
}
