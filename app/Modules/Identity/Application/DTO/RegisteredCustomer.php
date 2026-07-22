<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

final readonly class RegisteredCustomer
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}
}
