<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Authentication;

use App\Modules\Administration\Application\Port\AdminChallengeCodeHasher;

final readonly class HmacAdminChallengeCodeHasher implements AdminChallengeCodeHasher
{
    public function hash(string $challengeId, string $plainCode): string
    {
        return hash_hmac('sha256', $challengeId.':'.$plainCode, (string) config('app.key'));
    }
}
