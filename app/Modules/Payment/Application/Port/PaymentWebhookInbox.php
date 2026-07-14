<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Port;

use App\Modules\Payment\Application\DTO\ProviderWebhookEvent;

interface PaymentWebhookInbox
{
    public function receive(ProviderWebhookEvent $event): void;

    public function claim(?string $id = null): ?ProviderWebhookEvent;

    public function processed(string $id): void;

    public function retry(string $id, string $error, int $maxAttempts): void;

    public function recoverStale(int $staleAfterMinutes, int $maxAttempts): int;

    /** @return array{pending: int, processing: int, failed: int} */
    public function statusCounts(): array;
}
