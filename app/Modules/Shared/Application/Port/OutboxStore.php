<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Port;

interface OutboxStore
{
    /** @param array<string, mixed> $payload */
    public function add(string $id, string $type, string $aggregateId, array $payload): void;
}
