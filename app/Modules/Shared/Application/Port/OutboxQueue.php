<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Port;

use App\Modules\Shared\Application\DTO\OutboxMessage;

interface OutboxQueue
{
    public function claim(string $type): ?OutboxMessage;

    public function markProcessed(string $id): void;

    public function retry(string $id, string $error): void;
}
