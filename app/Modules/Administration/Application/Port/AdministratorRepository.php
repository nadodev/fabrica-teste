<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use App\Modules\Administration\Application\DTO\AdministratorAccount;
use App\Modules\Administration\Domain\AdminPermission;

interface AdministratorRepository
{
    public function findByEmail(string $email): ?AdministratorAccount;

    public function findById(int $userId): ?AdministratorAccount;

    /** @return list<AdministratorAccount> */
    public function all(): array;

    /** @param list<AdminPermission> $permissions */
    public function grant(int $userId, array $permissions, int $grantedBy): void;

    public function revoke(int $userId): void;
}
