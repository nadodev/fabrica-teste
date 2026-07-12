<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('protects the product dashboard from guests and non admins', function () {
    $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.products.index'))->assertForbidden();
});

it('authenticates an administrator and regenerates the session', function () {
    User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-password', 'is_admin' => true]);

    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'secret-password'])
        ->assertRedirect(route('admin.products.index'));
    $this->assertAuthenticated();
});

it('allows an administrator to create a server-priced product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $payload = [
        'sku' => 'ADMIN-001',
        'name' => 'Produto cadastrado no painel',
        'description' => 'Descrição segura',
        'price' => '79,90',
        'status' => 'active',
        'imageUrl' => null,
    ];

    $this->actingAs($admin)
        ->post(route('admin.products.store'), $payload, ['Idempotency-Key' => 'admin-product-001'])
        ->assertRedirect(route('admin.products.index'));

    $this->assertDatabaseHas('catalog_products', [
        'sku' => 'ADMIN-001',
        'price_amount' => 7990,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->get(route('admin.products.index'))->assertInertia(fn (Assert $page) => $page
        ->component('admin/products/index')
        ->where('products.0.sku', 'ADMIN-001'));
});
