<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

interface AdminChallengeCodeHasher
{
    public function hash(string $challengeId, string $plainCode): string;
}
