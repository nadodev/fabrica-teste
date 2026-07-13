<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\Command;

use App\Modules\Customers\Application\Port\CustomerAccountRepository;
use DomainException;

final readonly class UpdateCustomerProfile
{
    public function __construct(private CustomerAccountRepository $accounts) {}

    public function handle(int $userId, string $name, string $phone, string $document): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new DomainException('Nome do cliente obrigatorio.');
        }

        $this->accounts->updateProfile($userId, $name, trim($phone), trim($document));
    }
}
