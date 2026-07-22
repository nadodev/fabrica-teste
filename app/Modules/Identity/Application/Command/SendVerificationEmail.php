<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command;

use App\Modules\Identity\Application\Port\CustomerNotificationSender;

final readonly class SendVerificationEmail
{
    public function __construct(private CustomerNotificationSender $notifications) {}

    public function handle(int $customerId): bool
    {
        return $this->notifications->sendEmailVerification($customerId);
    }
}
