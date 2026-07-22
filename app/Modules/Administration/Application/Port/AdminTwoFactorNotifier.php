<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

use DateTimeImmutable;

interface AdminTwoFactorNotifier
{
    public function send(int $userId, string $plainCode, DateTimeImmutable $expiresAt): void;
}
