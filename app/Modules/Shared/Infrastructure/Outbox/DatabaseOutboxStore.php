<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Outbox;

use App\Modules\Shared\Application\Port\OutboxStore;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseOutboxStore implements OutboxStore
{
    public function __construct(private ConnectionInterface $database) {}

    public function add(string $id, string $type, string $aggregateId, array $payload): void
    {
        $this->database->table('shared_outbox')->insertOrIgnore([
            'id' => $id,
            'type' => $type,
            'aggregate_id' => $aggregateId,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
