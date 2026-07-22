<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

use App\Modules\Administration\Domain\AdminChallengeStatus;

final readonly class AdminChallengeAttempt
{
    public function __construct(
        public AdminChallengeStatus $status,
        public ?int $userId,
        public bool $remember,
    ) {}
}
