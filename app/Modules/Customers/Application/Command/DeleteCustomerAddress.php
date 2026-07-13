<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\Command;

use App\Modules\Customers\Application\Port\CustomerAccountRepository;

final readonly class DeleteCustomerAddress
{
    public function __construct(private CustomerAccountRepository $accounts) {}

    public function handle(int $userId, string $addressId): void
    {
        $this->accounts->deleteAddress($userId, $addressId);
    }
}
