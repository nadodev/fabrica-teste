<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

final readonly class ProviderWebhookEvent
{
    /** @param array<string, scalar|null> $payment */
    public function __construct(public string $id, public string $event, public string $providerPaymentId, public array $payment) {}
}
