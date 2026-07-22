<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use App\Modules\Administration\Domain\AdminPermission;

interface AdminPermissionChecker
{
    public function allows(int $userId, AdminPermission $permission): bool;

    public function isSuperAdministrator(int $userId): bool;

    /** @return list<string> */
    public function permissionValues(int $userId): array;
}
