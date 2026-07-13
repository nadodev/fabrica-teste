<?php

use App\Models\User;
use App\Modules\Cart\Application\DTO\CartView;
use App\Support\MelhorEnvioClient;
use App\Support\ShippingToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
        'token' => app(ShippingToken::class)->encode('production-token'),
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
    DB::table('shipping_settings')->where('id', 1)->update([
        'is_enabled' => true,
        'environment' => 'production',
        'origin_zip' => '89600000',
        'token' => app(ShippingToken::class)->encode('expired-token'),
    ]);
    Http::fake([
        'https://melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response(['message' => 'Unauthenticated.'], 401),
    ]);

    app(MelhorEnvioClient::class)->quote('01001000', shippingCartView());
})->throws(RuntimeException::class, 'Gere um novo token');

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

it('keeps the encrypted shipping token out of the admin response', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $encrypted = app(ShippingToken::class)->encode('secret-production-token');
    DB::table('shipping_settings')->where('id', 1)->update(['token' => $encrypted]);

    $this->actingAs($admin)->get(route('admin.shipping.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('admin/shipping')
            ->where('shipping.hasToken', true)
            ->where('shipping.token', '')
            ->where('shipping.tokenPreview', 'salvo, termina em -token'));
});

it('requires a token from the newly selected Melhor Envio environment', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    DB::table('shipping_settings')->where('id', 1)->update([
        'environment' => 'sandbox',
        'token' => app(ShippingToken::class)->encode('sandbox-token'),
    ]);

    $this->actingAs($admin)->post(route('admin.shipping.update'), [
        'isEnabled' => true,
        'environment' => 'production',
        'originZip' => '89600-000',
        'token' => '',
        'options' => [],
    ], ['Idempotency-Key' => 'shipping-environment-change'])
        ->assertSessionHasErrors('token');
});
