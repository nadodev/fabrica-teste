<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Notification;

use App\Models\User;
use App\Modules\Administration\Application\Port\AdminTwoFactorNotifier;
use DateTimeImmutable;
use RuntimeException;

final readonly class LaravelAdminTwoFactorNotifier implements AdminTwoFactorNotifier
{
    public function send(int $userId, string $plainCode, DateTimeImmutable $expiresAt): void
    {
        $user = User::query()->find($userId)
            ?? throw new RuntimeException('Conta administrativa não encontrada.');

        $user->notify(new AdminTwoFactorCodeNotification($plainCode, $expiresAt));
    }
}
