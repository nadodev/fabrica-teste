<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Port;

interface PaymentGatewayReadiness
{
    public function assertReady(): void;
}
