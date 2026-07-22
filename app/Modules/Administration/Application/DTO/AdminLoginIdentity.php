<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

final readonly class AdminLoginIdentity
{
    public function __construct(
        public int $userId,
        public string $email,
        public bool $emailVerified,
        public bool $administrator,
    ) {}
}
