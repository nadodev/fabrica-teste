<?php

use App\Models\User;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Customers\Application\Command\SaveCustomerAddress;
use App\Modules\Customers\Application\DTO\CustomerAddressData;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

it('updates the authenticated customer profile', function () {
    $user = User::factory()->create(['name' => 'Nome antigo']);

    $this->actingAs($user)->put(route('cliente.profile.update'), [
        'name' => 'Maria Cliente',
        'phone' => '(11) 99999-9999',
        'document' => '123.456.789-01',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Maria Cliente',
        'phone' => '(11) 99999-9999',
        'document' => '123.456.789-01',
    ]);
});

it('creates edits and lists addresses owned by the customer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('cliente.addresses.store'), customerAddressPayload([
        'label' => 'Casa',
    ]))->assertRedirect();

    $addressId = (string) DB::table('customer_addresses')->where('user_id', $user->id)->value('id');
    $this->assertDatabaseHas('customer_addresses', [
        'id' => $addressId,
        'user_id' => $user->id,
        'label' => 'Casa',
        'postal_code' => '01001000',
        'is_default' => true,
    ]);

    $this->actingAs($user)->put(route('cliente.addresses.update', $addressId), customerAddressPayload([
        'label' => 'Escritorio',
        'street' => 'Avenida Paulista',
    ]))->assertRedirect();

    $this->actingAs($user)->get(route('cliente.conta'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('cliente/conta')
            ->where('profile.email', $user->email)
            ->has('addresses', 1)
            ->where('addresses.0.label', 'Escritorio')
            ->where('addresses.0.street', 'Avenida Paulista'));
});

it('keeps only one default address per type and promotes a replacement after deletion', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('cliente.addresses.store'), customerAddressPayload(['label' => 'Casa']));
    $firstId = (string) DB::table('customer_addresses')->where('user_id', $user->id)->value('id');

    $this->actingAs($user)->post(route('cliente.addresses.store'), customerAddressPayload([
        'label' => 'Trabalho',
        'isDefault' => true,
    ]));
    $secondId = (string) DB::table('customer_addresses')->where('user_id', $user->id)->where('id', '<>', $firstId)->value('id');

    $this->assertDatabaseHas('customer_addresses', ['id' => $firstId, 'is_default' => false]);
    $this->assertDatabaseHas('customer_addresses', ['id' => $secondId, 'is_default' => true]);

    $this->actingAs($user)->delete(route('cliente.addresses.destroy', $secondId))->assertRedirect();
    $this->assertDatabaseHas('customer_addresses', ['id' => $firstId, 'is_default' => true]);
});

it('does not allow a customer to change or delete another customers address', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $addressId = (string) Str::uuid();
    DB::table('customer_addresses')->insert([
        'id' => $addressId,
        'user_id' => $owner->id,
        'type' => 'shipping',
        'label' => 'Privado',
        'postal_code' => '01001000',
        'street' => 'Rua Privada',
        'number' => '10',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($other)->put(route('cliente.addresses.update', $addressId), customerAddressPayload())->assertNotFound();
    $this->actingAs($other)->delete(route('cliente.addresses.destroy', $addressId))->assertNotFound();
    $this->assertDatabaseHas('customer_addresses', ['id' => $addressId, 'user_id' => $owner->id]);
});

it('keeps a default address per type when the current default changes type', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('cliente.addresses.store'), customerAddressPayload(['label' => 'Casa']));
    $this->actingAs($user)->post(route('cliente.addresses.store'), customerAddressPayload([
        'label' => 'Trabalho',
        'isDefault' => false,
    ]));
    $homeId = (string) DB::table('customer_addresses')->where('user_id', $user->id)->where('label', 'Casa')->value('id');
    $workId = (string) DB::table('customer_addresses')->where('user_id', $user->id)->where('label', 'Trabalho')->value('id');

    $this->actingAs($user)->put(route('cliente.addresses.update', $homeId), customerAddressPayload([
        'type' => 'personal',
        'label' => 'Casa',
        'isDefault' => false,
    ]))->assertRedirect();

    $this->assertDatabaseHas('customer_addresses', ['id' => $homeId, 'type' => 'personal', 'is_default' => true]);
    $this->assertDatabaseHas('customer_addresses', ['id' => $workId, 'type' => 'shipping', 'is_default' => true]);
});

it('requires authentication and validates customer address data', function () {
    $this->post(route('cliente.addresses.store'), customerAddressPayload())->assertRedirect(route('cliente.login'));

    $user = User::factory()->create();
    $this->actingAs($user)->from(route('cliente.conta'))->post(route('cliente.addresses.store'), customerAddressPayload([
        'postalCode' => '123',
        'state' => 'Santa Catarina',
    ]))->assertRedirect(route('cliente.conta'))->assertSessionHasErrors(['postalCode', 'state']);

    $this->assertDatabaseCount('customer_addresses', 0);
});

it('rejects invalid address data at the application boundary', function () {
    $user = User::factory()->create();

    expect(fn () => app(SaveCustomerAddress::class)->handle(
        $user->id,
        null,
        new CustomerAddressData('unknown', 'Casa', '123', 'Rua A', '1', 'Cidade', 'Santa Catarina', true),
    ))->toThrow(DomainException::class, 'Dados do endereco invalidos.');

    $this->assertDatabaseCount('customer_addresses', 0);
});

it('prefills authenticated customer data and saved addresses in checkout', function () {
    $user = User::factory()->create([
        'name' => 'Cliente Preenchido',
        'email' => 'preenchido@example.com',
        'phone' => '(11) 98888-7777',
        'document' => '123.456.789-01',
    ]);
    $addressId = (string) Str::uuid();
    DB::table('customer_addresses')->insert([
        'id' => $addressId,
        'user_id' => $user->id,
        'type' => 'shipping',
        'label' => 'Casa',
        'postal_code' => '01001000',
        'street' => 'Praca da Se',
        'number' => '100',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $token = 'customer-prefill-cart';
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'PREFILL-001',
        'name' => 'Produto',
        'description' => '',
        'price_amount' => 10000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto', new Money(10000), 1, 'PREFILL-001');
    app(CartRepository::class)->save($cart);

    $this->actingAs($user)->withSession([
        'cart_token' => $token,
        'shipping_quote' => [
            'serviceId' => 'customer-address-shipping',
            'name' => 'Entrega teste',
            'companyName' => 'Transportadora teste',
            'priceAmount' => 1000,
            'deliveryTime' => 2,
        ],
    ])->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('checkout')
            ->where('customer.name', 'Cliente Preenchido')
            ->where('customer.phone', '(11) 98888-7777')
            ->has('savedAddresses', 1)
            ->where('savedAddresses.0.id', $addressId));
});

/** @param array<string, mixed> $overrides */
function customerAddressPayload(array $overrides = []): array
{
    return [...[
        'type' => 'shipping',
        'label' => 'Casa',
        'postalCode' => '01001-000',
        'street' => 'Praca da Se',
        'number' => '100',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'isDefault' => true,
    ], ...$overrides];
}
