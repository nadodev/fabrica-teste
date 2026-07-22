<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command;

use App\Modules\Identity\Application\Port\PasswordResetter;

final readonly class SendPasswordResetLink
{
    public function __construct(private PasswordResetter $passwords) {}

    public function handle(string $email): void
    {
        // The result is deliberately not exposed to prevent account enumeration.
        $this->passwords->sendLink($email);
    }
}
