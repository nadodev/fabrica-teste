<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure;

use App\Modules\Payment\Application\Port\PaymentGateway;
use App\Modules\Payment\Application\Port\PaymentGatewayReadiness;
use App\Modules\Payment\Application\Port\PaymentInstructionStore;
use App\Modules\Payment\Application\Port\PaymentReconciliationGateway;
use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Application\Port\PaymentWebhookInbox;
use App\Modules\Payment\Infrastructure\Gateway\AsaasPaymentGateway;
use App\Modules\Payment\Infrastructure\Gateway\FakePaymentGateway;
use App\Modules\Payment\Infrastructure\Persistence\DatabasePaymentInstructionStore;
use App\Modules\Payment\Infrastructure\Persistence\DatabasePaymentRepository;
use App\Modules\Payment\Infrastructure\Persistence\DatabasePaymentWebhookInbox;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentRepository::class, DatabasePaymentRepository::class);
        $this->app->bind(PaymentInstructionStore::class, DatabasePaymentInstructionStore::class);
        $this->app->bind(PaymentWebhookInbox::class, DatabasePaymentWebhookInbox::class);
        $this->app->bind(PaymentReconciliationGateway::class, AsaasPaymentGateway::class);
        $this->app->bind(PaymentGatewayReadiness::class, function ($app): PaymentGatewayReadiness {
            return $app->make(PaymentGateway::class);
        });
        $this->app->bind(PaymentGateway::class, function ($app): PaymentGateway {
            if (config('payment.gateway') === 'asaas') {
                return $app->make(AsaasPaymentGateway::class);
            }
            if (config('payment.gateway') === 'fake' && $app->environment('production')) {
                throw new RuntimeException('Fake payment gateway is forbidden in production.');
            }
            if (config('payment.gateway') !== 'fake') {
                throw new RuntimeException('Configured payment gateway is not available.');
            }

            return $app->make(FakePaymentGateway::class);
        });
    }
}
