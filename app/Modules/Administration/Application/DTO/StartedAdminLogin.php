<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

use DateTimeImmutable;

final readonly class StartedAdminLogin
{
    public function __construct(
        public string $challengeId,
        public DateTimeImmutable $expiresAt,
    ) {}
}
