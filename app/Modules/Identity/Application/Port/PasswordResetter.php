<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Port;

use App\Modules\Identity\Application\DTO\ResetPasswordData;
use App\Modules\Identity\Application\Result\PasswordResetResult;
use App\Modules\Identity\Application\Result\ResetLinkResult;

interface PasswordResetter
{
    public function sendLink(string $email): ResetLinkResult;

    public function reset(ResetPasswordData $data): PasswordResetResult;
}
