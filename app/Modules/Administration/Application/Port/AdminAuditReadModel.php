<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

interface AdminAuditReadModel
{
    /** @return list<array<string, mixed>> */
    public function latest(int $limit = 50): array;
}
