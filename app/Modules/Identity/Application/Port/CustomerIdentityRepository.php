<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Port;

use App\Modules\Identity\Application\DTO\RegisterCustomerData;
use App\Modules\Identity\Application\DTO\RegisteredCustomer;

interface CustomerIdentityRepository
{
    public function create(RegisterCustomerData $data): RegisteredCustomer;

    public function markEmailVerified(int $customerId): bool;
}
