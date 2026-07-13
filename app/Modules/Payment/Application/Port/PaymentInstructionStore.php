<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Port;

use App\Modules\Payment\Application\DTO\PaymentInstructions;
use App\Modules\Payment\Application\DTO\PaymentResult;

interface PaymentInstructionStore
{
    public function save(string $paymentId, PaymentResult $result): void;

    public function find(string $paymentId): ?PaymentInstructions;
}
