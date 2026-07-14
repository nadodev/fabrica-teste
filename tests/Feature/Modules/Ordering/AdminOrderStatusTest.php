<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('changes an order through the domain policy and records the administrator', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $orderId = adminStatusOrder('paid');
    $createdAt = DB::table('ordering_orders')->where('id', $orderId)->value('created_at');

    $this->actingAs($admin)->post(route('admin.orders.status', $orderId), [
        'status' => 'processing',
    ], ['Idempotency-Key' => (string) Str::uuid()])->assertRedirect();

    $this->assertDatabaseHas('ordering_orders', ['id' => $orderId, 'status' => 'processing']);
    $this->assertDatabaseHas('ordering_order_status_history', [
        'order_id' => $orderId,
        'from_status' => 'paid',
        'to_status' => 'processing',
        'admin_user_id' => $admin->id,
    ]);
    expect((string) DB::table('ordering_orders')->where('id', $orderId)->value('created_at'))->toBe((string) $createdAt);
    $this->assertDatabaseCount('ordering_order_items', 1);
});

it('rejects an invalid administrative status jump without changing the order', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $orderId = adminStatusOrder('awaiting_payment');

    $this->actingAs($admin)->post(route('admin.orders.status', $orderId), [
        'status' => 'delivered',
    ], ['Idempotency-Key' => (string) Str::uuid()])
        ->assertRedirect()
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('ordering_orders', ['id' => $orderId, 'status' => 'awaiting_payment']);
    $this->assertDatabaseCount('ordering_order_status_history', 0);
});

function adminStatusOrder(string $status): string
{
    $cartId = (string) Str::uuid();
    $orderId = (string) Str::uuid();
    DB::table('cart_carts')->insert([
        'id' => $cartId, 'token_hash' => hash('sha256', $cartId), 'currency' => 'BRL', 'status' => 'converted',
        'version' => 1, 'expires_at' => now()->addDay(), 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
    ]);
    DB::table('ordering_orders')->insert([
        'id' => $orderId, 'number' => 'PED-'.Str::upper(Str::random(8)), 'cart_id' => $cartId, 'status' => $status,
        'checkout_type' => 'payment', 'customer_name' => 'Cliente', 'customer_email' => 'cliente@example.com',
        'customer_phone' => '11999999999', 'shipping_zip' => '01001000', 'shipping_address' => 'Rua Teste',
        'shipping_number' => '10', 'shipping_city' => 'Sao Paulo', 'shipping_state' => 'SP', 'delivery_method' => 'shipping',
        'shipping_amount' => 0, 'payment_method' => 'pix', 'payment_status' => $status === 'paid' ? 'paid' : 'pending',
        'subtotal_amount' => 5000, 'discount_amount' => 0, 'total_amount' => 5000, 'currency' => 'BRL',
        'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
    ]);
    DB::table('ordering_order_items')->insert([
        'order_id' => $orderId, 'product_id' => (string) Str::uuid(), 'sku' => 'STATUS-001', 'name' => 'Produto',
        'unit_price_amount' => 5000, 'price_currency' => 'BRL', 'quantity' => 1, 'subtotal_amount' => 5000,
    ]);

    return $orderId;
}
