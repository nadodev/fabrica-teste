<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Port;

interface AdminOrderReadModel
{
    /** @return list<array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function find(string $orderId): ?array;

    /** @return list<array<string, mixed>> */
    public function statusHistory(string $orderId): array;
}
