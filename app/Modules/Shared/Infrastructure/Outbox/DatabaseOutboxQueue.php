<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Outbox;

use App\Modules\Shared\Application\DTO\OutboxMessage;
use App\Modules\Shared\Application\Port\OutboxQueue;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseOutboxQueue implements OutboxQueue
{
    public function __construct(private ConnectionInterface $database) {}

    public function claim(string $type): ?OutboxMessage
    {
        return $this->database->transaction(function () use ($type): ?OutboxMessage {
            $this->database->table('shared_outbox')
                ->where('type', $type)
                ->where('status', 'processing')
                ->where('updated_at', '<=', now()->subMinutes(15))
                ->update([
                    'status' => 'pending',
                    'available_at' => now(),
                    'last_error' => 'Processing lease expired before completion.',
                    'updated_at' => now(),
                ]);

            $record = $this->database->table('shared_outbox')
                ->where('type', $type)
                ->where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                return null;
            }

            $this->database->table('shared_outbox')->where('id', $record->id)->update([
                'status' => 'processing',
                'attempts' => (int) $record->attempts + 1,
                'updated_at' => now(),
            ]);

            $payload = json_decode((string) $record->payload, true, 512, JSON_THROW_ON_ERROR);

            return new OutboxMessage((string) $record->id, (string) $record->type, is_array($payload) ? $payload : []);
        }, 3);
    }

    public function markProcessed(string $id): void
    {
        $this->database->table('shared_outbox')->where('id', $id)->update([
            'status' => 'processed',
            'processed_at' => now(),
            'last_error' => null,
            'updated_at' => now(),
        ]);
    }

    public function retry(string $id, string $error): void
    {
        $this->database->table('shared_outbox')->where('id', $id)->update([
            'status' => 'pending',
            'available_at' => now()->addMinutes(5),
            'last_error' => mb_substr($error, 0, 2000),
            'updated_at' => now(),
        ]);
    }
}
