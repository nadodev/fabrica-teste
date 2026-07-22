<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Authentication;

use App\Models\User;
use App\Modules\Administration\Application\DTO\AdminLoginIdentity;
use App\Modules\Administration\Application\Port\AdminCredentialVerifier;
use Illuminate\Support\Facades\Hash;

final class EloquentAdminCredentialVerifier implements AdminCredentialVerifier
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function verify(string $email, string $password): ?AdminLoginIdentity
    {
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            Hash::check($password, self::DUMMY_PASSWORD_HASH);

            return null;
        }
        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $this->map($user);
    }

    public function findEligible(int $userId): ?AdminLoginIdentity
    {
        $user = User::query()->find($userId);

        return $user === null ? null : $this->map($user);
    }

    private function map(User $user): AdminLoginIdentity
    {
        return new AdminLoginIdentity(
            (int) $user->getAuthIdentifier(),
            $user->email,
            $user->hasVerifiedEmail(),
            $user->is_admin,
        );
    }
}
