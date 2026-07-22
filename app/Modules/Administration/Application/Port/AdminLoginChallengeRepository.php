<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use App\Modules\Administration\Domain\AdminLoginChallenge;
use DateTimeImmutable;

interface AdminLoginChallengeRepository
{
    public function invalidateOutstandingForUser(int $userId, DateTimeImmutable $now): void;

    public function add(AdminLoginChallenge $challenge): void;

    public function findForUpdate(string $challengeId): ?AdminLoginChallenge;

    public function save(AdminLoginChallenge $challenge): void;

    public function invalidate(string $challengeId, DateTimeImmutable $now): void;

    public function pruneExpiredBefore(DateTimeImmutable $threshold): int;
}
