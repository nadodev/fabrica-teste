<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Password;

use App\Models\User;
use App\Modules\Identity\Application\DTO\ResetPasswordData;
use App\Modules\Identity\Application\Port\PasswordResetter;
use App\Modules\Identity\Application\Result\PasswordResetResult;
use App\Modules\Identity\Application\Result\ResetLinkResult;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

final class LaravelPasswordResetter implements PasswordResetter
{
    public function sendLink(string $email): ResetLinkResult
    {
        try {
            return match (Password::sendResetLink(['email' => mb_strtolower($email)])) {
                Password::RESET_LINK_SENT => ResetLinkResult::Sent,
                Password::RESET_THROTTLED => ResetLinkResult::Throttled,
                default => ResetLinkResult::UnknownUser,
            };
        } catch (Throwable $exception) {
            report($exception);

            return ResetLinkResult::DeliveryFailed;
        }
    }

    public function reset(ResetPasswordData $data): PasswordResetResult
    {
        $status = Password::reset(
            [
                'email' => mb_strtolower($data->email),
                'password' => $data->password,
                'password_confirmation' => $data->password,
                'token' => $data->token,
            ],
            function (User $customer, string $password): void {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordResetEvent($customer));
            },
        );

        return match ($status) {
            Password::PASSWORD_RESET => PasswordResetResult::Reset,
            Password::INVALID_USER => PasswordResetResult::UnknownUser,
            default => PasswordResetResult::InvalidToken,
        };
    }
}
