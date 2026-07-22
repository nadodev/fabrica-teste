<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use App\Modules\Administration\Application\DTO\AdminAuditEntry;

interface AdminAuditRecorder
{
    public function record(AdminAuditEntry $entry): void;
}
