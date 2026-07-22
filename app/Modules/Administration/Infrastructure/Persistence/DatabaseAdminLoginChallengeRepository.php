<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Modules\Administration\Application\Port\AdminLoginChallengeRepository;
use App\Modules\Administration\Domain\AdminLoginChallenge;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use stdClass;

final readonly class DatabaseAdminLoginChallengeRepository implements AdminLoginChallengeRepository
{
    public function __construct(private ConnectionInterface $database) {}

    public function invalidateOutstandingForUser(int $userId, DateTimeImmutable $now): void
    {
        $this->database->table('admin_login_challenges')
            ->where('user_id', $userId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $now, 'updated_at' => $now]);
    }

    public function add(AdminLoginChallenge $challenge): void
    {
        $this->database->table('admin_login_challenges')->insert([
            'id' => $challenge->id,
            'user_id' => $challenge->userId,
            'code_hash' => $challenge->codeHash,
            'remember' => $challenge->remember,
            'attempts' => $challenge->attempts,
            'max_attempts' => $challenge->maxAttempts,
            'expires_at' => $challenge->expiresAt,
            'consumed_at' => $challenge->consumedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function findForUpdate(string $challengeId): ?AdminLoginChallenge
    {
        $record = $this->database->table('admin_login_challenges')
            ->where('id', $challengeId)
            ->lockForUpdate()
            ->first();

        return $record instanceof stdClass ? $this->map($record) : null;
    }

    public function save(AdminLoginChallenge $challenge): void
    {
        $this->database->table('admin_login_challenges')
            ->where('id', $challenge->id)
            ->update([
                'attempts' => $challenge->attempts,
                'consumed_at' => $challenge->consumedAt,
                'updated_at' => now(),
            ]);
    }

    public function invalidate(string $challengeId, DateTimeImmutable $now): void
    {
        $this->database->table('admin_login_challenges')
            ->where('id', $challengeId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => $now, 'updated_at' => $now]);
    }

    public function pruneExpiredBefore(DateTimeImmutable $threshold): int
    {
        return $this->database->table('admin_login_challenges')
            ->where('expires_at', '<', $threshold)
            ->delete();
    }

    private function map(stdClass $record): AdminLoginChallenge
    {
        return new AdminLoginChallenge(
            (string) $record->id,
            (int) $record->user_id,
            (string) $record->code_hash,
            (bool) $record->remember,
            new DateTimeImmutable((string) $record->expires_at),
            (int) $record->attempts,
            (int) $record->max_attempts,
            $record->consumed_at === null ? null : new DateTimeImmutable((string) $record->consumed_at),
        );
    }
}
