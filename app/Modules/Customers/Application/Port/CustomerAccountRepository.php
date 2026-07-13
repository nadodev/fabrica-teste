<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\Port;

use App\Modules\Customers\Application\DTO\CustomerAddressData;

interface CustomerAccountRepository
{
    /** @return array{profile: array{name: string, email: string, phone: string, document: string}, addresses: list<array{id: string, type: string, label: string, postalCode: string, street: string, number: string, city: string, state: string, isDefault: bool}>} */
    public function find(int $userId): array;

    public function updateProfile(int $userId, string $name, string $phone, string $document): void;

    public function saveAddress(int $userId, ?string $addressId, CustomerAddressData $address): string;

    public function deleteAddress(int $userId, string $addressId): void;
}
