<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Payment\Application\DTO\ProviderWebhookEvent;
use App\Modules\Payment\Application\Port\PaymentWebhookInbox;

final readonly class ReceiveAsaasWebhook
{
    public function __construct(private PaymentWebhookInbox $inbox) {}

    /** @param array<string, scalar|null> $payment */
    public function handle(string $id, string $event, string $providerPaymentId, array $payment): void
    {
        $this->inbox->receive(new ProviderWebhookEvent($id, $event, $providerPaymentId, $payment));
    }
}
