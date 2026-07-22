<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Notification;

use App\Models\User;
use App\Modules\Identity\Application\Port\CustomerNotificationSender;
use Throwable;

final class LaravelCustomerNotificationSender implements CustomerNotificationSender
{
    public function sendEmailVerification(int $customerId): bool
    {
        $customer = User::query()->findOrFail($customerId);
        if ($customer->hasVerifiedEmail()) {
            return true;
        }

        try {
            $customer->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
