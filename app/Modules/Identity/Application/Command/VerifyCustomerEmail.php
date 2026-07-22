<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command;

use App\Modules\Identity\Application\Port\CustomerIdentityRepository;

final readonly class VerifyCustomerEmail
{
    public function __construct(private CustomerIdentityRepository $customers) {}

    public function handle(int $customerId): bool
    {
        return $this->customers->markEmailVerified($customerId);
    }
}
