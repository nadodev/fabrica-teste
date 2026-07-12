<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Idempotency;

use App\Modules\Shared\Application\Idempotency\IdempotencyClaim;
use App\Modules\Shared\Application\Idempotency\IdempotencyOutcome;
use App\Modules\Shared\Application\Port\IdempotencyStore;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

final readonly class DatabaseIdempotencyStore implements IdempotencyStore
{
    public function __construct(private ConnectionInterface $database) {}

    public function claim(string $scope, string $key, string $fingerprint, int $ttlSeconds): IdempotencyClaim
    {
        try {
            $this->database->table('idempotency_keys')->insert([
                'scope' => $scope,
                'key' => $key,
                'fingerprint' => $fingerprint,
                'status' => 'processing',
                'expires_at' => now()->addSeconds($ttlSeconds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return new IdempotencyClaim(IdempotencyOutcome::Acquired);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }
        }

        $record = $this->database->table('idempotency_keys')->where('scope', $scope)->where('key', $key)->first();

        if ($record === null || now()->greaterThan($record->expires_at)) {
            $this->release($scope, $key);

            return $this->claim($scope, $key, $fingerprint, $ttlSeconds);
        }

        if (! hash_equals((string) $record->fingerprint, $fingerprint)) {
            return new IdempotencyClaim(IdempotencyOutcome::Conflict);
        }

        if ($record->status !== 'completed') {
            return new IdempotencyClaim(IdempotencyOutcome::InProgress);
        }

        return new IdempotencyClaim(
            IdempotencyOutcome::Replay,
            (int) $record->response_code,
            json_decode((string) $record->response_headers, true, flags: JSON_THROW_ON_ERROR),
            (string) $record->response_body,
        );
    }

    public function complete(string $scope, string $key, int $responseCode, array $headers, string $body): void
    {
        $this->database->table('idempotency_keys')->where('scope', $scope)->where('key', $key)->update([
            'status' => 'completed',
            'response_code' => $responseCode,
            'response_headers' => json_encode($headers, JSON_THROW_ON_ERROR),
            'response_body' => $body,
            'updated_at' => now(),
        ]);
    }

    public function release(string $scope, string $key): void
    {
        $this->database->table('idempotency_keys')->where('scope', $scope)->where('key', $key)->delete();
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique constraint');
    }
}
