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
        'customer_email' => $email,
        'total_amount' => 1000,
        'currency' => 'BRL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $orderId;
}
