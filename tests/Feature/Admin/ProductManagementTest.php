<?php

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        'weightGrams' => 850,
        'widthCentimeters' => 24,
        'heightCentimeters' => 8,
        'lengthCentimeters' => 35,
    ];

    $this->actingAs($admin)
        ->post(route('admin.products.store'), $payload, ['Idempotency-Key' => 'admin-product-001'])
        ->assertRedirect(route('admin.products.index'));

    $this->assertDatabaseHas('catalog_products', [
        'sku' => 'ADMIN-001',
        'price_amount' => 7990,
        'status' => 'active',
        'weight_grams' => 850,
        'width_centimeters' => 24,
        'height_centimeters' => 8,
        'length_centimeters' => 35,
    ]);

    $this->actingAs($admin)->get(route('admin.products.index'))->assertInertia(fn (Assert $page) => $page
        ->component('admin/products/index')
        ->where('products.0.sku', 'ADMIN-001'));
});

it('uploads, updates and archives a product without deleting its history', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $image = UploadedFile::fake()->image('product.jpg', 800, 800);

    $this->actingAs($admin)->post(route('admin.products.store'), [
        'sku' => 'IMAGE-001',
        'name' => 'Produto com imagem',
        'description' => 'Versão inicial',
        'price' => '100,00',
        'status' => 'active',
        'image' => $image,
    ], ['Idempotency-Key' => 'admin-image-create'])->assertRedirect();

    $product = ProductRecord::query()->where('sku', 'IMAGE-001')->firstOrFail();
    expect($product->image_url)->toStartWith('/storage/products/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $product->image_url));

    $this->actingAs($admin)->post(route('admin.products.update', $product->id), [
        '_method' => 'PUT',
        'name' => 'Produto atualizado',
        'description' => 'Nova versão',
        'price' => '119,90',
        'status' => 'draft',
        'removeImage' => true,
    ], ['Idempotency-Key' => 'admin-image-update'])->assertRedirect();

    $this->assertDatabaseHas('catalog_products', ['id' => $product->id, 'name' => 'Produto atualizado', 'price_amount' => 11990, 'status' => 'draft', 'image_url' => null]);

    $this->actingAs($admin)->delete(route('admin.products.destroy', $product->id), [], ['Idempotency-Key' => 'admin-product-archive'])->assertRedirect();
    $this->assertDatabaseHas('catalog_products', ['id' => $product->id, 'status' => 'archived']);
});

it('rejects unsafe image formats', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->from(route('admin.products.create'))->post(route('admin.products.store'), [
        'sku' => 'SVG-001',
        'name' => 'Imagem insegura',
        'price' => '10,00',
        'status' => 'draft',
        'image' => UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml'),
    ], ['Idempotency-Key' => 'admin-svg-rejected'])->assertRedirect(route('admin.products.create'))->assertSessionHasErrors('image');
});
