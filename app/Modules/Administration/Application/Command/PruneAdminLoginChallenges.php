<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\Port\AdminLoginChallengeRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class PruneAdminLoginChallenges
{
    public function __construct(private AdminLoginChallengeRepository $challenges) {}

    public function handle(int $retentionDays): int
    {
        if ($retentionDays < 1 || $retentionDays > 365) {
            throw new InvalidArgumentException('A retenção dos desafios deve estar entre 1 e 365 dias.');
        }

        return $this->challenges->pruneExpiredBefore(
            (new DateTimeImmutable)->modify(sprintf('-%d days', $retentionDays)),
        );
    }
}
