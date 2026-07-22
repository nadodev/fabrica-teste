<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use App\Modules\Administration\Application\DTO\AdminLoginIdentity;

interface AdminCredentialVerifier
{
    public function verify(string $email, string $password): ?AdminLoginIdentity;

    public function findEligible(int $userId): ?AdminLoginIdentity;
}
