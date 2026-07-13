<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\Command;

use App\Modules\Customers\Application\DTO\CustomerAddressData;
use App\Modules\Customers\Application\Port\CustomerAccountRepository;
use DomainException;

final readonly class SaveCustomerAddress
{
    public function __construct(private CustomerAccountRepository $accounts) {}

    public function handle(int $userId, ?string $addressId, CustomerAddressData $address): string
    {
        $postalCode = preg_replace('/\D+/', '', $address->postalCode);
        if (
            ! in_array($address->type, ['personal', 'shipping'], true)
            || trim($address->label) === ''
            || trim($address->street) === ''
            || trim($address->number) === ''
            || trim($address->city) === ''
            || ! is_string($postalCode)
            || strlen($postalCode) !== 8
            || preg_match('/^[A-Za-z]{2}$/', trim($address->state)) !== 1
        ) {
            throw new DomainException('Dados do endereco invalidos.');
        }

        return $this->accounts->saveAddress($userId, $addressId, $address);
    }
}
