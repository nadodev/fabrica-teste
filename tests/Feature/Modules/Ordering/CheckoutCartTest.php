<?php

use App\Mail\OrderPlacedMail;
use App\Models\User;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Inventory\Domain\Exception\InsufficientStock;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;
use App\Modules\Ordering\Application\Command\CheckoutCart;
use App\Modules\Ordering\Application\Command\ProcessOrderOutbox;
use App\Modules\Ordering\Application\DTO\CheckoutData;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Inertia\Testing\AssertableInertia as Assert;

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
    $data = new CheckoutData(
        'payment', 'Cliente', 'cliente@example.com', '11999999999', null,
        '01001000', 'Rua Teste', '10', 'Sao Paulo', 'SP', 'shipping', 'pix', null, null, checkoutShippingQuote(),
    );
    $order = $checkout->handle('0190f566-c399-79e3-a553-7e5fb8d83462', $token, $data);
    $replayed = $checkout->handle('0190f566-c399-79e3-a553-7e5fb8d83463', $token, $data);

    expect($order->status())->toBe(OrderStatus::AwaitingPayment)
        ->and($order->total()->amount)->toBe(25980)
        ->and($replayed->id)->toBe($order->id);
    $this->assertDatabaseCount('ordering_orders', 1);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'variation_key' => null, 'on_hand' => 3, 'reserved' => 2]);
    $this->assertDatabaseHas('cart_carts', ['id' => $cart->id, 'status' => 'converted']);
    $this->assertDatabaseHas('shared_outbox', ['aggregate_id' => $order->id, 'status' => 'pending']);
    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'pending', 'amount' => 25980]);
});

it('persists the complete checkout snapshot and consumes a coupon inside the transaction', function () {
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'SNAPSHOT-001',
        'name' => 'Produto completo',
        'description' => '',
        'price_amount' => 10000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('snapshot-stock-001', $productId, 5);

    DB::table('commerce_coupons')->insert([
        'id' => (string) Str::uuid(),
        'code' => 'PRIMEIRA10',
        'description' => 'Desconto de teste',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'minimum_amount' => 0,
        'usage_limit' => 1,
        'used_count' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $token = 'complete-checkout-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto completo', new Money(10000), 1, 'SNAPSHOT-001');
    app(CartRepository::class)->save($cart);

    $order = app(CheckoutCart::class)->handle((string) Str::uuid(), $token, new CheckoutData(
        'payment', 'Maria Cliente', 'maria@example.com', '11999999999', '12345678901',
        '01001000', 'Praca da Se', '100', 'Sao Paulo', 'SP', 'shipping', 'pix', 'Entregar na recepcao',
        'PRIMEIRA10', ['name' => 'PAC', 'companyName' => 'Correios', 'priceAmount' => 1500, 'deliveryTime' => 5],
    ));

    expect($order->subtotal()->amount)->toBe(10000)
        ->and($order->details()->discount->amount)->toBe(1000)
        ->and($order->details()->shipping->amount)->toBe(1500)
        ->and($order->total()->amount)->toBe(10500);
    $this->assertDatabaseHas('ordering_orders', [
        'id' => $order->id,
        'customer_email' => 'maria@example.com',
        'shipping_service' => 'PAC',
        'shipping_amount' => 1500,
        'coupon_code' => 'PRIMEIRA10',
        'discount_amount' => 1000,
        'total_amount' => 10500,
    ]);
    $this->assertDatabaseHas('commerce_coupons', ['code' => 'PRIMEIRA10', 'used_count' => 1]);
});

it('rolls back coupon usage, order and outbox when stock reservation fails', function () {
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'ROLLBACK-001',
        'name' => 'Produto sem estoque',
        'description' => '',
        'price_amount' => 5000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('rollback-stock-001', $productId, 1);
    DB::table('commerce_coupons')->insert([
        'id' => (string) Str::uuid(),
        'code' => 'ROLLBACK10',
        'description' => '',
        'discount_type' => 'fixed',
        'discount_value' => 1000,
        'minimum_amount' => 0,
        'usage_limit' => 1,
        'used_count' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $token = 'rollback-checkout-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto sem estoque', new Money(5000), 2, 'ROLLBACK-001');
    app(CartRepository::class)->save($cart);
    $data = new CheckoutData(
        'payment', 'Cliente', 'cliente@example.com', '11999999999', null,
        '01001000', 'Rua Teste', '10', 'Sao Paulo', 'SP', 'shipping', 'pix', null, 'ROLLBACK10', checkoutShippingQuote(),
    );

    expect(fn () => app(CheckoutCart::class)->handle((string) Str::uuid(), $token, $data))
        ->toThrow(InsufficientStock::class);
    $this->assertDatabaseHas('commerce_coupons', ['code' => 'ROLLBACK10', 'used_count' => 0]);
    $this->assertDatabaseHas('cart_carts', ['id' => $cart->id, 'status' => 'active']);
    $this->assertDatabaseCount('ordering_orders', 0);
    $this->assertDatabaseCount('shared_outbox', 0);
    $this->assertDatabaseCount('payment_payments', 0);
    $this->assertDatabaseCount('inventory_reservations', 0);
});

it('processes order email only after the outbox message exists', function () {
    Mail::fake();
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'MAIL-001',
        'name' => 'Produto notificado',
        'description' => '',
        'price_amount' => 7000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('mail-stock-001', $productId, 1);
    $token = 'mail-checkout-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto notificado', new Money(7000), 1, 'MAIL-001');
    app(CartRepository::class)->save($cart);

    $order = app(CheckoutCart::class)->handle((string) Str::uuid(), $token, new CheckoutData(
        'payment', 'Cliente Email', 'email@example.com', '11999999999', null,
        '01001000', 'Rua Teste', '10', 'Sao Paulo', 'SP', 'shipping', 'pix', null, null, checkoutShippingQuote(),
    ));

    Mail::assertNothingSent();
    expect(app(ProcessOrderOutbox::class)->handle())->toBe(1);
    Mail::assertSent(OrderPlacedMail::class, fn (OrderPlacedMail $mail): bool => $mail->hasTo('email@example.com'));
    $this->assertDatabaseHas('shared_outbox', ['aggregate_id' => $order->id, 'status' => 'processed']);
});

it('maps the public checkout request to the transactional use case', function () {
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'HTTP-001',
        'name' => 'Produto via HTTP',
        'description' => '',
        'price_amount' => 8900,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('http-stock-001', $productId, 2);
    $token = 'http-checkout-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto via HTTP', new Money(8900), 1, 'HTTP-001');
    app(CartRepository::class)->save($cart);

    $user = User::factory()->create(['name' => 'Cliente Vinculado', 'email' => 'vinculado@example.com']);
    $response = $this->actingAs($user)->withSession(['cart_token' => $token, 'shipping_quote' => checkoutShippingQuote()])->post(route('checkout.store'), [
        'customerName' => 'Nome adulterado',
        'customerEmail' => 'outro@example.com',
        'customerPhone' => '11999999999',
        'customerDocument' => '12345678901',
        'shippingZip' => '01001000',
        'shippingAddress' => 'Rua HTTP',
        'shippingNumber' => '50',
        'shippingCity' => 'Sao Paulo',
        'shippingState' => 'SP',
        'checkoutType' => 'payment',
        'deliveryMethod' => 'shipping',
        'paymentMethod' => 'pix',
        'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()]);

    $orderNumber = (string) DB::table('ordering_orders')->value('number');
    $response->assertRedirect(route('checkout.success', ['order' => $orderNumber]));
    $response->assertSessionMissing('cart_token');
    $this->assertDatabaseHas('ordering_orders', [
        'customer_user_id' => $user->id,
        'customer_name' => 'Cliente Vinculado',
        'customer_email' => 'vinculado@example.com',
        'delivery_method' => 'shipping',
        'total_amount' => 8900,
    ]);
});

it('keeps the cart active and does not create an order when Asaas is not ready', function () {
    config()->set('payment.gateway', 'asaas');
    config()->set('payment.asaas.live_enabled', false);
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'ASAAS-NOT-READY',
        'name' => 'Produto sem gateway',
        'description' => '',
        'price_amount' => 5000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('asaas-not-ready-stock', $productId, 1);
    $token = 'asaas-not-ready-cart';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto sem gateway', new Money(5000), 1, 'ASAAS-NOT-READY');
    app(CartRepository::class)->save($cart);

    $response = $this->from(route('checkout'))->withSession(['cart_token' => $token, 'shipping_quote' => checkoutShippingQuote()])->post(route('checkout.store'), [
        'customerName' => 'Cliente Gateway',
        'customerEmail' => 'gateway@example.com',
        'customerPhone' => '11999999999',
        'customerDocument' => '12345678909',
        'shippingZip' => '01001000',
        'shippingAddress' => 'Rua Gateway',
        'shippingNumber' => '10',
        'shippingCity' => 'Sao Paulo',
        'shippingState' => 'SP',
        'checkoutType' => 'payment',
        'deliveryMethod' => 'shipping',
        'paymentMethod' => 'pix',
        'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()]);

    $response->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('checkout')
        ->assertSessionHas('cart_token', $token);
    $this->assertDatabaseHas('cart_carts', ['id' => $cart->id, 'status' => 'active']);
    $this->assertDatabaseCount('ordering_orders', 0);
    $this->assertDatabaseCount('payment_payments', 0);
});

it('creates and securely displays Pix instructions immediately after checkout', function () {
    config()->set('payment.gateway', 'asaas');
    config()->set('payment.asaas.live_enabled', true);
    config()->set('payment.asaas.base_url', 'https://api.asaas.com/v3');
    config()->set('payment.asaas.api_key', '$aact_prod_test_only');
    Http::fake([
        'https://api.asaas.com/v3/payments?*' => Http::response(['data' => []]),
        'https://api.asaas.com/v3/customers' => Http::response(['id' => 'cus_checkout_pix']),
        'https://api.asaas.com/v3/payments' => Http::response([
            'id' => 'pay_checkout_pix',
            'status' => 'PENDING',
            'invoiceUrl' => 'https://asaas.com/i/checkout-pix',
        ]),
        'https://api.asaas.com/v3/payments/pay_checkout_pix/pixQrCode' => Http::response([
            'encodedImage' => 'aW1hZ2VtLXBpeA==',
            'payload' => '000201PIX-CHECKOUT-SEGURO',
            'expirationDate' => '2026-07-14 23:59:59',
        ]),
    ]);
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'PIX-HTTP-001',
        'name' => 'Produto PIX',
        'description' => '',
        'price_amount' => 2500,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('pix-http-stock', $productId, 1);
    $token = 'pix-http-cart-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto PIX', new Money(2500), 1, 'PIX-HTTP-001');
    app(CartRepository::class)->save($cart);
    $user = User::factory()->create(['name' => 'Cliente PIX', 'email' => 'pix@example.com']);

    $response = $this->actingAs($user)->withSession(['cart_token' => $token, 'shipping_quote' => checkoutShippingQuote()])->post(route('checkout.store'), [
        'customerName' => 'Cliente PIX',
        'customerEmail' => 'pix@example.com',
        'customerPhone' => '11999999999',
        'customerDocument' => '12345678909',
        'shippingZip' => '01001000',
        'shippingAddress' => 'Rua PIX',
        'shippingNumber' => '10',
        'shippingCity' => 'Sao Paulo',
        'shippingState' => 'SP',
        'checkoutType' => 'payment',
        'deliveryMethod' => 'shipping',
        'paymentMethod' => 'pix',
        'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()]);

    $orderNumber = (string) DB::table('ordering_orders')->value('number');
    $successUrl = route('checkout.success', ['order' => $orderNumber]);
    $response->assertRedirect($successUrl);
    $this->get($successUrl)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->component('pedido-confirmado')
        ->where('paymentMethod', 'pix')
        ->where('paymentStatus', 'pending')
        ->where('instructions.pixPayload', '000201PIX-CHECKOUT-SEGURO')
        ->where('instructions.pixEncodedImage', 'aW1hZ2VtLXBpeA=='));
    $this->assertDatabaseHas('payment_instructions', ['pix_payload' => '000201PIX-CHECKOUT-SEGURO']);

    $other = User::factory()->create();
    $this->actingAs($other)->withSession(['checkout_order_id' => null])->get($successUrl)->assertNotFound();
});

it('validates credit card fields without flashing the number or security code', function () {
    config()->set('payment.gateway', 'asaas');

    $response = $this->from(route('checkout'))->post(route('checkout.store'), [
        'customerName' => 'Cliente Cartao',
        'customerEmail' => 'cartao@example.com',
        'customerPhone' => '11999999999',
        'customerDocument' => '12345678909',
        'shippingZip' => '01001000',
        'shippingAddress' => 'Rua Cartao',
        'shippingNumber' => '10',
        'shippingCity' => 'Sao Paulo',
        'shippingState' => 'SP',
        'checkoutType' => 'payment',
        'deliveryMethod' => 'shipping',
        'paymentMethod' => 'credit_card',
        'cardHolderName' => 'CLIENTE CARTAO',
        'cardNumber' => '111111111111111a',
        'cardExpiryMonth' => '01',
        'cardExpiryYear' => (string) now()->subYear()->year,
        'cardCcv' => '12',
        'privacyAccepted' => true,
    ], ['Idempotency-Key' => (string) Str::uuid()]);

    $response->assertRedirect(route('checkout'))
        ->assertSessionMissing('_old_input.cardNumber')
        ->assertSessionMissing('_old_input.cardCcv');
    $sessionErrors = $response->getSession()->all()['errors'] ?? [];
    $validationKeys = $sessionErrors instanceof ViewErrorBag
        ? $sessionErrors->getBag('default')->keys()
        : array_keys((array) ($sessionErrors['default']['messages'] ?? []));
    expect($validationKeys)->toContain('cardNumber', 'cardExpiryMonth', 'cardExpiryYear', 'cardCcv');
});

it('processes a credit card immediately without persisting sensitive card data', function () {
    config()->set('payment.gateway', 'asaas');
    config()->set('payment.asaas.live_enabled', true);
    config()->set('payment.asaas.base_url', 'https://api.asaas.com/v3');
    config()->set('payment.asaas.api_key', '$aact_prod_test_only');
    Http::fake([
        'https://api.asaas.com/v3/payments?*' => Http::response(['data' => []]),
        'https://api.asaas.com/v3/customers' => Http::response(['id' => 'cus_checkout_card']),
        'https://api.asaas.com/v3/payments' => Http::response([
            'id' => 'pay_checkout_card',
            'status' => 'CONFIRMED',
        ]),
    ]);
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'CARD-HTTP-001',
        'name' => 'Produto Cartao',
        'description' => '',
        'price_amount' => 7500,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('card-http-stock', $productId, 1);
    $token = 'card-http-cart-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto Cartao', new Money(7500), 1, 'CARD-HTTP-001');
    app(CartRepository::class)->save($cart);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
        ->withSession(['cart_token' => $token, 'shipping_quote' => checkoutShippingQuote()])
        ->post(route('checkout.store'), [
            'customerName' => 'Cliente Cartao',
            'customerEmail' => 'cartao@example.com',
            'customerPhone' => '(11) 99999-9999',
            'customerDocument' => '123.456.789-09',
            'shippingZip' => '01001-000',
            'shippingAddress' => 'Rua Cartao',
            'shippingNumber' => '10',
            'shippingCity' => 'Sao Paulo',
            'shippingState' => 'SP',
            'checkoutType' => 'payment',
            'deliveryMethod' => 'shipping',
            'paymentMethod' => 'credit_card',
            'cardHolderName' => 'CLIENTE CARTAO',
            'cardNumber' => '5162306219378829',
            'cardExpiryMonth' => '05',
            'cardExpiryYear' => '2030',
            'cardCcv' => '318',
            'privacyAccepted' => true,
        ], ['Idempotency-Key' => (string) Str::uuid()]);

    $orderNumber = (string) DB::table('ordering_orders')->value('number');
    $response->assertRedirect(route('checkout.success', ['order' => $orderNumber]))
        ->assertSessionMissing('cart_token')
        ->assertSessionMissing('_old_input.cardNumber')
        ->assertSessionMissing('_old_input.cardCcv');
    $this->assertDatabaseHas('payment_payments', [
        'provider_payment_id' => 'pay_checkout_card',
        'status' => 'paid',
    ]);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.asaas.com/v3/payments'
        && $request['creditCard']['number'] === '5162306219378829'
        && $request['creditCard']['ccv'] === '318'
        && $request['remoteIp'] === '203.0.113.20');
});

it('restores the cart and returns to checkout when Asaas declines the card', function () {
    config()->set('payment.gateway', 'asaas');
    config()->set('payment.asaas.live_enabled', true);
    config()->set('payment.asaas.base_url', 'https://api.asaas.com/v3');
    config()->set('payment.asaas.api_key', '$aact_prod_test_only');
    Http::fake([
        'https://api.asaas.com/v3/payments?*' => Http::response(['data' => []]),
        'https://api.asaas.com/v3/customers' => Http::response(['id' => 'cus_checkout_declined']),
        'https://api.asaas.com/v3/payments' => Http::response([
            'errors' => [['code' => 'invalid_credit_card', 'description' => 'Cartao recusado']],
        ], 400),
    ]);
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'CARD-DECLINED-001',
        'name' => 'Produto Cartao Recusado',
        'description' => '',
        'price_amount' => 7500,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('card-declined-stock', $productId, 1);
    $token = 'card-declined-cart-token';
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto Cartao Recusado', new Money(7500), 1, 'CARD-DECLINED-001');
    app(CartRepository::class)->save($cart);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.21'])
        ->withSession(['cart_token' => $token, 'shipping_quote' => checkoutShippingQuote()])
        ->post(route('checkout.store'), [
            'customerName' => 'Cliente Recusado',
            'customerEmail' => 'recusado@example.com',
            'customerPhone' => '(11) 99999-9999',
            'customerDocument' => '123.456.789-09',
            'shippingZip' => '01001-000',
            'shippingAddress' => 'Rua Cartao',
            'shippingNumber' => '10',
            'shippingCity' => 'Sao Paulo',
            'shippingState' => 'SP',
            'checkoutType' => 'payment',
            'deliveryMethod' => 'shipping',
            'paymentMethod' => 'credit_card',
            'cardHolderName' => 'CLIENTE RECUSADO',
            'cardNumber' => '5162306219378829',
            'cardExpiryMonth' => '05',
            'cardExpiryYear' => '2030',
            'cardCcv' => '318',
            'privacyAccepted' => true,
        ], ['Idempotency-Key' => (string) Str::uuid()]);

    $response->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('cardNumber')
        ->assertSessionHas('cart_token', $token)
        ->assertSessionMissing('_old_input.cardNumber')
        ->assertSessionMissing('_old_input.cardCcv');
    $this->assertDatabaseHas('ordering_orders', ['cart_id' => $cart->id, 'status' => 'cancelled', 'payment_status' => 'refused']);
    $this->assertDatabaseHas('payment_payments', ['status' => 'declined', 'provider_payment_id' => null]);
    $this->assertDatabaseHas('cart_carts', ['id' => $cart->id, 'status' => 'converted']);
    $this->assertDatabaseHas('cart_carts', ['token_hash' => hash('sha256', $token), 'status' => 'active']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 1, 'reserved' => 0]);
    $this->withSession(['cart_token' => $token, 'shipping_quote' => checkoutShippingQuote()])->get(route('checkout'))->assertOk();
});

/** @return array{serviceId: string, name: string, companyName: string, priceAmount: int, deliveryTime: int} */
function checkoutShippingQuote(): array
{
    return [
        'serviceId' => 'test-shipping',
        'name' => 'Entrega teste',
        'companyName' => 'Transportadora teste',
        'priceAmount' => 0,
        'deliveryTime' => 2,
    ];
}
