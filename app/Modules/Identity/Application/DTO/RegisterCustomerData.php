<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

final readonly class RegisterCustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
