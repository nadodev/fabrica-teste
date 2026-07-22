<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

final readonly class AdministratorAccount
{
    /** @param list<string> $permissions */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $emailVerified,
        public bool $administrator,
        public bool $superAdministrator,
        public array $permissions,
    ) {}
}
