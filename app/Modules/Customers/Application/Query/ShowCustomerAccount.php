<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\Query;

use App\Modules\Customers\Application\Port\CustomerAccountRepository;

final readonly class ShowCustomerAccount
{
    public function __construct(private CustomerAccountRepository $accounts) {}

    /** @return array{profile: array{name: string, email: string, phone: string, document: string}, addresses: list<array{id: string, type: string, label: string, postalCode: string, street: string, number: string, city: string, state: string, isDefault: bool}>} */
    public function handle(int $userId): array
    {
        return $this->accounts->find($userId);
    }
}
