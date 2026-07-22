<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command;

use App\Modules\Identity\Application\DTO\ResetPasswordData;
use App\Modules\Identity\Application\Port\PasswordResetter;
use App\Modules\Identity\Application\Result\PasswordResetResult;

final readonly class ResetCustomerPassword
{
    public function __construct(private PasswordResetter $passwords) {}

    public function handle(ResetPasswordData $data): PasswordResetResult
    {
        return $this->passwords->reset($data);
    }
}
