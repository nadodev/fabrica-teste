<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Port;

interface AdminChallengeCodeGenerator
{
    public function challengeId(): string;

    public function plainCode(): string;
}
