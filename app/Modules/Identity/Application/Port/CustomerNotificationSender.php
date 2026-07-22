<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Port;

interface CustomerNotificationSender
{
    public function sendEmailVerification(int $customerId): bool;
}
