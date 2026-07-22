<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Authentication;

use App\Modules\Administration\Application\Port\AdminChallengeCodeGenerator;
use Illuminate\Support\Str;

final class SecureAdminChallengeCodeGenerator implements AdminChallengeCodeGenerator
{
    public function challengeId(): string
    {
        return (string) Str::uuid();
    }

    public function plainCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
