<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\Port\AdminAuditRetention;
use DateTimeImmutable;

final readonly class PruneAdminAuditLogs
{
    public function __construct(private AdminAuditRetention $audit) {}

    public function handle(int $retentionDays): int
    {
        $days = max(30, min($retentionDays, 3650));

        return $this->audit->pruneBefore(new DateTimeImmutable("-{$days} days"));
    }
}
