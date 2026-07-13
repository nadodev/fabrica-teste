<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Payment\Application\Port\PaymentGatewayReadiness;

final readonly class EnsurePaymentGatewayReady
{
    public function __construct(private PaymentGatewayReadiness $gateway) {}

    public function handle(): void
    {
        $this->gateway->assertReady();
    }
}
