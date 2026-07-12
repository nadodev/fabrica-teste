<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class PaymentRequest
{
    /** @param array<string, scalar|null> $customer @param array<string, scalar|null> $metadata */
    public function __construct(public string $orderId, public int $amount, public string $currency, public string $methodToken, public array $customer, public array $metadata = []) {}
}
