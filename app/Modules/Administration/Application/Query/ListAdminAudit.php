<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Query;

use App\Modules\Administration\Application\Port\AdminAuditReadModel;

final readonly class ListAdminAudit
{
    public function __construct(private AdminAuditReadModel $audit) {}

    /** @return list<array<string, mixed>> */
    public function handle(int $limit = 50): array
    {
        return $this->audit->latest(max(1, min($limit, 100)));
    }
}
