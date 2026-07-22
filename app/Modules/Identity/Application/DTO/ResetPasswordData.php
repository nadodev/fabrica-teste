<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $token,
    ) {}
}
