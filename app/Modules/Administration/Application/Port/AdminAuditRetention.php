<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use DateTimeImmutable;

interface AdminAuditRetention
{
    public function pruneBefore(DateTimeImmutable $threshold): int;
}
