<?php

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;
use App\Modules\Ordering\Application\Command\CheckoutCart;
use App\Modules\Ordering\Application\DTO\CheckoutData;
use App\Modules\Payment\Application\Command\ProcessAsaasWebhooks;
use App\Modules\Payment\Application\Command\ProcessPayment;
use App\Modules\Payment\Application\Command\ProcessPaymentOutbox;
use App\Modules\Payment\Application\Command\ReconcileAsaasPayments;
use App\Modules\Payment\Application\Command\RecoverStuckPayment;
use App\Modules\Payment\Application\Command\RefundPayment;
use App\Modules\Payment\Application\DTO\CreditCardData;
use App\Modules\Payment\Application\Exception\PaymentCardDeclined;
use App\Modules\Payment\Application\Exception\PaymentGatewayTimeout;
use App\Modules\Payment\Application\Port\PaymentGateway;
use App\Modules\Payment\Application\Query\ShowCheckoutSuccess;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('payment.gateway', 'fake');
});

function checkoutForFakePayment(int $stock = 3, int $quantity = 2, string $method = 'pix', ?string $coupon = null): array
{
    $productId = (string) Str::uuid();
    ProductRecord::query()->create([
        'id' => $productId,
        'sku' => 'PAY-'.Str::upper(Str::random(8)),
        'name' => 'Produto pago',
        'description' => '',
        'price_amount' => 5000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
    app(DatabaseStockGateway::class)->receive('payment-stock-'.$productId, $productId, $stock);
    $token = 'payment-cart-'.Str::random(12);
    $cart = new Cart((string) Str::uuid(), hash('sha256', $token));
    $cart->add($productId, 'Produto pago', new Money(5000), $quantity, (string) ProductRecord::query()->findOrFail($productId)->sku);
    app(CartRepository::class)->save($cart);
    $order = app(CheckoutCart::class)->handle((string) Str::uuid(), $token, new CheckoutData(
        'payment', 'Cliente Pagamento', 'pagamento@example.com', '11999999999', null,
        '01001000', 'Rua Teste', '10', 'Sao Paulo', 'SP', 'pickup', $method, null, $coupon, null,
    ));

    return [$order, $productId];
}

it('approves payment once and confirms the stock reservation', function () {
    config()->set('payment.fake_outcome', 'approved');
    [$order, $productId] = checkoutForFakePayment();

    expect(app(ProcessPaymentOutbox::class)->handle())->toBe(1)
        ->and(app(ProcessPaymentOutbox::class)->handle())->toBe(0);
    app(ProcessPayment::class)->handle($order->id);

    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'paid', 'amount' => 10000]);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'paid', 'payment_status' => 'paid']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 1, 'reserved' => 0]);
    $this->assertDatabaseHas('inventory_reservations', ['product_id' => $productId, 'status' => 'confirmed']);
    expect(DB::table('payment_attempts')->count())->toBe(1)
        ->and(DB::table('payment_fake_transactions')->count())->toBe(1);
});

it('declines payment definitively and releases the reservation', function () {
    config()->set('payment.fake_outcome', 'declined');
    [$order, $productId] = checkoutForFakePayment();

    expect(app(ProcessPaymentOutbox::class)->handle())->toBe(1);

    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'declined', 'failure_code' => 'declined']);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'refused']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 3, 'reserved' => 0]);
    $this->assertDatabaseHas('inventory_reservations', ['product_id' => $productId, 'status' => 'released']);
});

it('cancels a card order when Asaas refuses it without creating a provider charge', function () {
    DB::table('commerce_coupons')->insert([
        'id' => (string) Str::uuid(),
        'code' => 'RETRY10',
        'description' => 'Cupom recuperavel',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'minimum_amount' => 0,
        'usage_limit' => 1,
        'used_count' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    [$order, $productId] = checkoutForFakePayment(method: 'credit_card', coupon: 'RETRY10');
    $this->assertDatabaseHas('commerce_coupons', ['code' => 'RETRY10', 'used_count' => 1]);
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('charge')->once()->andThrow(new PaymentCardDeclined('Cartao recusado.'));
    app()->instance(PaymentGateway::class, $gateway);

    expect(fn () => app(ProcessPayment::class)->handle($order->id, new CreditCardData(
        'CLIENTE PAGAMENTO', '5162306219378829', '05', '2030', '318', '203.0.113.30',
    )))->toThrow(PaymentCardDeclined::class);

    $this->assertDatabaseHas('payment_payments', [
        'order_id' => $order->id,
        'status' => 'declined',
        'provider_payment_id' => null,
    ]);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'refused']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 3, 'reserved' => 0]);
    $this->assertDatabaseHas('commerce_coupons', ['code' => 'RETRY10', 'used_count' => 0]);
    expect(DB::table('cart_carts')->where('status', 'active')->count())->toBe(1)
        ->and(DB::table('cart_items')->whereIn('cart_id', DB::table('cart_carts')->where('status', 'active')->select('id'))->count())->toBe(1);
});

it('keeps timeout retryable without duplicating the provider charge', function () {
    config()->set('payment.fake_outcome', 'timeout');
    [$order, $productId] = checkoutForFakePayment();

    expect(app(ProcessPaymentOutbox::class)->handle())->toBe(0)
        ->and(fn () => app(ProcessPayment::class)->handle($order->id))->toThrow(PaymentGatewayTimeout::class);

    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'pending', 'failure_code' => 'timeout']);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 3, 'reserved' => 2]);
    expect(DB::table('payment_attempts')->where('status', 'timeout')->count())->toBe(2)
        ->and(DB::table('payment_fake_transactions')->count())->toBe(1);
});

it('returns a failed Asaas attempt to pending instead of leaving it stuck processing', function () {
    config()->set('payment.gateway', 'asaas');
    config()->set('payment.asaas.live_enabled', false);
    [$order] = checkoutForFakePayment();

    expect(app(ProcessPaymentOutbox::class)->handle())->toBe(0);

    $this->assertDatabaseHas('payment_payments', [
        'order_id' => $order->id,
        'status' => 'pending',
        'failure_code' => 'gateway_error',
    ]);
    $this->assertDatabaseHas('payment_attempts', [
        'payment_id' => DB::table('payment_payments')->where('order_id', $order->id)->value('id'),
        'status' => 'failed',
        'response_code' => 'gateway_error',
    ]);
    expect(app(ShowCheckoutSuccess::class)->handle($order->number, $order->id, null)?->paymentFailureCode)
        ->toBe('gateway_error');
});

it('manually recovers only a payment stuck before receiving a provider ID', function () {
    [$order] = checkoutForFakePayment();
    DB::table('payment_payments')->where('order_id', $order->id)->update([
        'status' => 'processing',
        'provider_payment_id' => null,
    ]);

    expect(app(RecoverStuckPayment::class)->handle($order->number))->toBeTrue()
        ->and(app(RecoverStuckPayment::class)->handle($order->number))->toBeFalse();
    $this->assertDatabaseHas('payment_payments', [
        'order_id' => $order->id,
        'status' => 'pending',
        'failure_code' => 'manual_recovery',
    ]);
});

it('refunds an approved payment idempotently', function () {
    config()->set('payment.fake_outcome', 'approved');
    [$order] = checkoutForFakePayment();
    app(ProcessPaymentOutbox::class)->handle();

    app(RefundPayment::class)->handle($order->id);
    app(RefundPayment::class)->handle($order->id);

    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'refunded']);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'refunded', 'payment_status' => 'refunded']);
    expect(DB::table('payment_attempts')->where('operation', 'refund')->count())->toBe(1)
        ->and(DB::table('payment_fake_refunds')->count())->toBe(1)
        ->and((int) DB::table('payment_fake_transactions')->value('refunded_amount'))->toBe(10000);
});

it('compensates an approval when the stock reservation expired', function () {
    config()->set('payment.fake_outcome', 'approved');
    [$order, $productId] = checkoutForFakePayment();
    DB::table('inventory_reservations')->where('product_id', $productId)->update(['expires_at' => now()->subMinute()]);

    expect(app(ProcessPaymentOutbox::class)->handle())->toBe(1);

    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'declined']);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'refused']);
    $this->assertDatabaseHas('inventory_reservations', ['product_id' => $productId, 'status' => 'released']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 3, 'reserved' => 0]);
    expect((int) DB::table('payment_fake_transactions')->value('refunded_amount'))->toBe(10000)
        ->and(DB::table('payment_fake_refunds')->count())->toBe(1);
});

it('authenticates, deduplicates and processes an Asaas payment webhook', function () {
    config()->set('payment.asaas.webhook_token', str_repeat('w', 40));
    [$order, $productId] = checkoutForFakePayment();
    DB::table('payment_payments')->where('order_id', $order->id)->update(['provider' => 'asaas', 'provider_payment_id' => 'pay_webhook_1']);
    $payload = [
        'id' => 'evt_webhook_1',
        'event' => 'PAYMENT_RECEIVED',
        'payment' => ['id' => 'pay_webhook_1', 'status' => 'RECEIVED', 'billingType' => 'PIX', 'externalReference' => $order->id, 'value' => 100.0],
    ];

    $this->postJson('/webhooks/asaas', $payload)->assertForbidden();
    $headers = ['asaas-access-token' => str_repeat('w', 40)];
    $this->postJson('/webhooks/asaas', $payload, $headers)->assertOk();
    $this->postJson('/webhooks/asaas', $payload, $headers)->assertOk();

    expect(DB::table('payment_webhook_events')->count())->toBe(1)
        ->and(app(ProcessAsaasWebhooks::class)->handle())->toBe(1);
    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'paid']);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'paid', 'payment_status' => 'paid']);
    $this->assertDatabaseHas('inventory_stock_levels', ['product_id' => $productId, 'on_hand' => 1, 'reserved' => 0]);
    $this->assertDatabaseHas('payment_webhook_events', ['id' => 'evt_webhook_1', 'status' => 'processed']);
});

it('reflects partial refunds and chargebacks received from Asaas', function () {
    config()->set('payment.asaas.webhook_token', str_repeat('w', 40));
    [$order] = checkoutForFakePayment();
    DB::table('payment_payments')->where('order_id', $order->id)->update(['provider' => 'asaas', 'provider_payment_id' => 'pay_webhook_state']);
    $headers = ['asaas-access-token' => str_repeat('w', 40)];

    $this->postJson('/webhooks/asaas', [
        'id' => 'evt_state_paid',
        'event' => 'PAYMENT_RECEIVED',
        'payment' => ['id' => 'pay_webhook_state', 'status' => 'RECEIVED', 'billingType' => 'PIX', 'value' => 100],
    ], $headers)->assertOk();
    expect(app(ProcessAsaasWebhooks::class)->handle())->toBe(1);

    $this->postJson('/webhooks/asaas', [
        'id' => 'evt_state_partial',
        'event' => 'PAYMENT_PARTIALLY_REFUNDED',
        'payment' => ['id' => 'pay_webhook_state', 'status' => 'RECEIVED', 'billingType' => 'PIX', 'refundedValue' => 25],
    ], $headers)->assertOk();
    expect(app(ProcessAsaasWebhooks::class)->handle())->toBe(1);
    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'partially_refunded', 'refunded_amount' => 2500]);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'paid', 'payment_status' => 'partially_refunded']);

    $this->postJson('/webhooks/asaas', [
        'id' => 'evt_state_chargeback',
        'event' => 'PAYMENT_CHARGEBACK_REQUESTED',
        'payment' => [
            'id' => 'pay_webhook_state',
            'status' => 'CHARGEBACK_REQUESTED',
            'chargeback' => ['status' => 'REQUESTED', 'reason' => 'FRAUD'],
        ],
    ], $headers)->assertOk();
    expect(app(ProcessAsaasWebhooks::class)->handle())->toBe(1);
    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'chargeback', 'failure_code' => 'requested']);
    $this->assertDatabaseHas('ordering_orders', ['id' => $order->id, 'status' => 'paid', 'payment_status' => 'chargeback']);
});

it('reconciles a missed Asaas partial refund and is inert while live access is disabled', function () {
    config()->set('payment.fake_outcome', 'approved');
    [$order] = checkoutForFakePayment();
    app(ProcessPaymentOutbox::class)->handle();
    DB::table('payment_payments')->where('order_id', $order->id)->update(['provider' => 'asaas', 'provider_payment_id' => 'pay_reconcile_state']);
    config()->set('payment.gateway', 'asaas');
    config()->set('payment.asaas.live_enabled', false);
    Http::fake([
        'https://api.asaas.com/v3/payments/pay_reconcile_state' => Http::response([
            'id' => 'pay_reconcile_state',
            'status' => 'RECEIVED',
            'billingType' => 'PIX',
            'refunds' => [['status' => 'DONE', 'value' => 15]],
        ]),
    ]);

    expect(app(ReconcileAsaasPayments::class)->handle())->toBe(0);
    Http::assertNothingSent();

    config()->set('payment.asaas.live_enabled', true);
    config()->set('payment.asaas.api_key', '$aact_prod_test_only');
    config()->set('payment.asaas.base_url', 'https://api.asaas.com/v3');

    expect(app(ReconcileAsaasPayments::class)->handle())->toBe(1);
    $this->assertDatabaseHas('payment_payments', ['order_id' => $order->id, 'status' => 'partially_refunded', 'refunded_amount' => 1500]);
});
