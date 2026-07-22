<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

final readonly class CompletedAdminLogin
{
    public function __construct(
        public int $userId,
        public bool $remember,
    ) {}
}
