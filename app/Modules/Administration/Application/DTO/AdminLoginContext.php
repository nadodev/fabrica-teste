<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

final readonly class AdminLoginContext
{
    public function __construct(
        public ?string $ipHash,
        public ?string $userAgent,
    ) {}
}
