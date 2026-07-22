<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain;

use DateTimeImmutable;
use DomainException;

final class AdminLoginChallenge
{
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly string $codeHash,
        public readonly bool $remember,
        public readonly DateTimeImmutable $expiresAt,
        public int $attempts,
        public readonly int $maxAttempts,
        public ?DateTimeImmutable $consumedAt = null,
    ) {
        if ($maxAttempts < 1 || $attempts < 0 || $attempts > $maxAttempts) {
            throw new DomainException('Estado inválido do desafio administrativo.');
        }
    }

    public function verify(string $submittedHash, DateTimeImmutable $now): AdminChallengeStatus
    {
        if ($this->consumedAt !== null) {
            return AdminChallengeStatus::Consumed;
        }
        if ($now >= $this->expiresAt) {
            $this->consumedAt = $now;

            return AdminChallengeStatus::Expired;
        }
        if ($this->attempts >= $this->maxAttempts) {
            $this->consumedAt = $now;

            return AdminChallengeStatus::Locked;
        }
        if (! hash_equals($this->codeHash, $submittedHash)) {
            $this->attempts++;
            if ($this->attempts >= $this->maxAttempts) {
                $this->consumedAt = $now;

                return AdminChallengeStatus::Locked;
            }

            return AdminChallengeStatus::Invalid;
        }

        $this->consumedAt = $now;

        return AdminChallengeStatus::Success;
    }

    public function invalidate(DateTimeImmutable $now): void
    {
        $this->consumedAt ??= $now;
    }
}
