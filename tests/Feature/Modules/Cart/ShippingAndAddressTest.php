<?php

use App\Models\User;
use App\Modules\Cart\Application\DTO\CartView;
use App\Support\MelhorEnvioClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

function shippingCartView(): CartView
{
    return new CartView([
        [
            'cartItemKey' => 'item-shipping-1',
            'unitPriceAmount' => 5000,
            'quantity' => 2,
        ],
    ], 10000, 0, 0, 10000, 'BRL');
}

it('uses the official production endpoint and authenticated Melhor Envio headers', function () {
    config()->set('services.melhor_envio.token', 'production-token');
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
    ]);
    Http::fake([
        'https://melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([[
            'id' => 1,
            'name' => 'PAC',
            'price' => '27.90',
            'custom_price' => '25.90',
            'custom_delivery_time' => 5,
            'company' => ['name' => 'Correios'],
        ]]),
    ]);

    $quotes = app(MelhorEnvioClient::class)->quote('01001-000', shippingCartView());

    expect($quotes)->toHaveCount(1)
        ->and($quotes[0]['priceAmount'])->toBe(2590);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://melhorenvio.com.br/api/v2/me/shipment/calculate'
        && $request->hasHeader('Authorization', 'Bearer production-token')
        && $request->hasHeader('User-Agent'));
});

it('returns an actionable message when Melhor Envio refuses the token', function () {
    config()->set('services.melhor_envio.token', 'expired-token');
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
    ]);
    Http::fake([
        'https://melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response(['message' => 'Unauthenticated.'], 401),
    ]);

    app(MelhorEnvioClient::class)->quote('01001000', shippingCartView());
})->throws(RuntimeException::class, 'Integracoes > Permissoes de Acesso');

it('looks up a postal address through the protected ViaCEP adapter', function () {
    Http::fake([
        'https://viacep.com.br/ws/01001000/json/' => Http::response([
            'cep' => '01001-000',
            'logradouro' => 'Praca da Se',
            'bairro' => 'Se',
            'localidade' => 'Sao Paulo',
            'uf' => 'SP',
        ]),
    ]);

    $this->getJson(route('endereco.cep', ['zip' => '01001-000']))
        ->assertOk()
        ->assertJson([
            'postalCode' => '01001000',
            'street' => 'Praca da Se',
            'neighborhood' => 'Se',
            'city' => 'Sao Paulo',
            'state' => 'SP',
        ]);
});

it('handles an unknown postal code without inventing an address', function () {
    Http::fake([
        'https://viacep.com.br/ws/99999999/json/' => Http::response(['erro' => true]),
    ]);

    $this->getJson(route('endereco.cep', ['zip' => '99999-999']))
        ->assertNotFound()
        ->assertJson(['message' => 'CEP nao encontrado.']);
});

it('reports only whether the environment token is configured to the admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    config()->set('services.melhor_envio.token', 'secret-production-token');

    $this->actingAs($admin)->get(route('admin.shipping.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('admin/shipping')
            ->where('shipping.hasConfiguredToken', true)
            ->missing('shipping.token')
            ->missing('shipping.tokenPreview'));
});

it('requires an environment token before enabling Melhor Envio', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    config()->set('services.melhor_envio.token', null);

    $this->actingAs($admin)->post(route('admin.shipping.update'), [
        'isEnabled' => true,
        'environment' => 'production',
        'originZip' => '89600-000',
        'options' => [],
    ], ['Idempotency-Key' => 'shipping-environment-change'])
        ->assertSessionHasErrors('isEnabled');
});

it('requires an origin postal code before enabling Melhor Envio', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    config()->set('services.melhor_envio.token', 'environment-token');

    $this->actingAs($admin)->post(route('admin.shipping.update'), [
        'isEnabled' => true,
        'environment' => 'production',
        'originZip' => '',
        'options' => [],
    ], ['Idempotency-Key' => 'shipping-missing-origin'])
        ->assertSessionHasErrors('originZip');
});

it('reports missing Melhor Envio settings separately', function () {
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '',
    ]);
    config()->set('services.melhor_envio.token', '');

    expect(fn () => app(MelhorEnvioClient::class)->quote('01001000', shippingCartView()))
        ->toThrow(RuntimeException::class, 'MELHOR_ENVIO_TOKEN nao foi carregado');

    config()->set('services.melhor_envio.token', 'environment-token');

    expect(fn () => app(MelhorEnvioClient::class)->quote('01001000', shippingCartView()))
        ->toThrow(RuntimeException::class, 'O CEP de origem nao esta salvo');
});

it('diagnoses Melhor Envio readiness without exposing the token', function () {
    config()->set('services.melhor_envio.token', 'secret-environment-token');
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
    ]);

    $this->artisan('shipping:diagnose')
        ->expectsOutputToContain('MELHOR_ENVIO_TOKEN: CONFIGURADO')
        ->expectsOutputToContain('CEP de origem: CONFIGURADO')
        ->doesntExpectOutputToContain('secret-environment-token')
        ->assertSuccessful();
});

it('verifies Melhor Envio credentials against the selected environment', function () {
    config()->set('services.melhor_envio.token', 'secret-environment-token');
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
    ]);
    Http::fake([
        'https://melhorenvio.com.br/api/v2/me' => Http::response(['id' => 'user-id']),
    ]);

    $this->artisan('shipping:diagnose --verify')
        ->expectsOutputToContain('Autenticacao remota do Melhor Envio: APROVADA')
        ->doesntExpectOutputToContain('secret-environment-token')
        ->assertSuccessful();
});

it('reports when Melhor Envio rejects the configured credential', function () {
    config()->set('services.melhor_envio.token', 'rejected-environment-token');
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
    ]);
    Http::fake([
        'https://melhorenvio.com.br/api/v2/me' => Http::response(['message' => 'Unauthenticated.'], 401),
    ]);

    $this->artisan('shipping:diagnose --verify')
        ->expectsOutputToContain('Autenticacao remota do Melhor Envio: RECUSADA')
        ->doesntExpectOutputToContain('rejected-environment-token')
        ->assertFailed();
});

it('saves shipping settings without accepting a database token', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    config()->set('services.melhor_envio.token', 'environment-token');

    $this->actingAs($admin)->post(route('admin.shipping.update'), [
        'isEnabled' => true,
        'environment' => 'production',
        'originZip' => '89600-000',
        'token' => 'must-not-be-persisted',
        'options' => ['pickupEnabled' => true],
    ], ['Idempotency-Key' => 'shipping-environment-token'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('shipping_settings', [
        'id' => 1,
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
    ]);
    expect(Schema::hasColumn('shipping_settings', 'token'))->toBeFalse();
});

it('does not keep a Melhor Envio token column in the database', function () {
    expect(Schema::hasColumn('shipping_settings', 'token'))->toBeFalse();
});
