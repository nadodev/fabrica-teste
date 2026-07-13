<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Port;

use App\Modules\Payment\Application\DTO\PaymentRequest;
use App\Modules\Payment\Application\DTO\PaymentResult;

interface PaymentGateway
{
    public function charge(PaymentRequest $request): PaymentResult;

    public function refund(string $transactionId, string $idempotencyKey, ?int $amount = null): PaymentResult;
}
