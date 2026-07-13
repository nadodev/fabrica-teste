<?php

use App\Modules\Payment\Application\DTO\PaymentRequest;
use App\Modules\Payment\Infrastructure\Gateway\AsaasPaymentGateway;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('payment.asaas.live_enabled', true);
    config()->set('payment.asaas.base_url', 'https://api.asaas.com/v3');
    config()->set('payment.asaas.api_key', '$aact_prod_test_only');
});

it('creates and reuses an Asaas customer before creating a pending charge', function () {
    Http::fake([
        'https://api.asaas.com/v3/payments?*' => Http::response(['data' => []]),
        'https://api.asaas.com/v3/customers' => Http::response(['id' => 'cus_123']),
        'https://api.asaas.com/v3/payments' => Http::response([
            'id' => 'pay_123',
            'status' => 'PENDING',
            'invoiceUrl' => 'https://asaas.com/i/123',
        ]),
    ]);
    $request = new PaymentRequest('order-asaas-1', 10990, 'BRL', 'pix', 'idem-1', [
        'name' => 'Cliente Asaas',
        'email' => 'cliente@example.com',
        'document' => '123.456.789-09',
        'phone' => '(11) 99999-9999',
    ]);

    $result = app(AsaasPaymentGateway::class)->charge($request);

    expect($result->transactionId)->toBe('pay_123')
        ->and($result->status)->toBe('pending')
        ->and($result->redirectUrl)->toBe('https://asaas.com/i/123');
    $this->assertDatabaseHas('payment_provider_customers', ['provider_customer_id' => 'cus_123']);
    Http::assertSentCount(3);
});

it('does not call Asaas while the production safety switch is disabled', function () {
    config()->set('payment.asaas.live_enabled', false);
    Http::fake();

    app(AsaasPaymentGateway::class)->charge(new PaymentRequest(
        'order-asaas-2', 1000, 'BRL', 'pix', 'idem-2',
        ['name' => 'Cliente', 'email' => 'cliente@example.com', 'document' => '12345678909'],
    ));
})->throws(RuntimeException::class, 'Asaas live charges are disabled');

it('reads the provider state and counts only completed refunds', function () {
    Http::fake([
        'https://api.asaas.com/v3/payments/pay_reconcile_1' => Http::response([
            'id' => 'pay_reconcile_1',
            'status' => 'RECEIVED',
            'billingType' => 'PIX',
            'refunds' => [
                ['status' => 'DONE', 'value' => 25.50],
                ['status' => 'PENDING', 'value' => 10.00],
                ['status' => 'DONE', 'value' => 4.50],
            ],
            'chargeback' => ['status' => 'REQUESTED', 'reason' => 'FRAUD'],
        ]),
    ]);

    $snapshot = app(AsaasPaymentGateway::class)->fetch('pay_reconcile_1');

    expect($snapshot->providerPaymentId)->toBe('pay_reconcile_1')
        ->and($snapshot->status)->toBe('RECEIVED')
        ->and($snapshot->refundedAmount)->toBe(3000)
        ->and($snapshot->chargebackStatus)->toBe('REQUESTED')
        ->and($snapshot->chargebackReason)->toBe('FRAUD');
    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api.asaas.com/v3/payments/pay_reconcile_1');
});
