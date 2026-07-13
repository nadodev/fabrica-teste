<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Port;

use App\Modules\Payment\Application\DTO\ProviderPaymentSnapshot;

interface PaymentReconciliationGateway
{
    public function fetch(string $providerPaymentId): ProviderPaymentSnapshot;
}
