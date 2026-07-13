<?php

declare(strict_types=1);

namespace App\Modules\Customers\Infrastructure\Persistence;

use App\Modules\Customers\Application\DTO\CustomerAddressData;
use App\Modules\Customers\Application\Port\CustomerAccountRepository;
use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final readonly class DatabaseCustomerAccountRepository implements CustomerAccountRepository
{
    public function __construct(private ConnectionInterface $database) {}

    public function find(int $userId): array
    {
        $user = $this->database->table('users')->where('id', $userId)->firstOrFail();
        $addresses = $this->database->table('customer_addresses')
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('type')
            ->orderBy('label')
            ->get()
            ->map(fn (object $address): array => [
                'id' => (string) $address->id,
                'type' => (string) $address->type,
                'label' => (string) $address->label,
                'postalCode' => (string) $address->postal_code,
                'street' => (string) $address->street,
                'number' => (string) $address->number,
                'city' => (string) $address->city,
                'state' => (string) $address->state,
                'isDefault' => (bool) $address->is_default,
            ])
            ->all();

        return [
            'profile' => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'phone' => (string) ($user->phone ?? ''),
                'document' => (string) ($user->document ?? ''),
            ],
            'addresses' => array_values($addresses),
        ];
    }

    public function updateProfile(int $userId, string $name, string $phone, string $document): void
    {
        $updated = $this->database->table('users')->where('id', $userId)->update([
            'name' => $name,
            'phone' => $phone === '' ? null : $phone,
            'document' => $document === '' ? null : $document,
            'updated_at' => now(),
        ]);

        if ($updated === 0 && ! $this->database->table('users')->where('id', $userId)->exists()) {
            throw new DomainException('Cliente nao encontrado.');
        }
    }

    public function saveAddress(int $userId, ?string $addressId, CustomerAddressData $address): string
    {
        return $this->database->transaction(function () use ($userId, $addressId, $address): string {
            $this->lockCustomer($userId);
            $id = $addressId ?? (string) Str::uuid();
            $existing = $addressId === null ? null : $this->database->table('customer_addresses')
                ->where('id', $addressId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($addressId !== null && $existing === null) {
                throw new DomainException('Endereco nao encontrado para esta conta.');
            }

            $addressesOfType = $this->database->table('customer_addresses')
                ->where('user_id', $userId)
                ->where('type', $address->type);
            if ($addressId !== null) {
                $addressesOfType->where('id', '<>', $addressId);
            }

            $isFirstOfType = ! $addressesOfType->exists();
            $keepsCurrentDefault = $existing !== null
                && (bool) $existing->is_default
                && (string) $existing->type === $address->type;
            $isDefault = $address->isDefault || $isFirstOfType || $keepsCurrentDefault;

            if ($isDefault) {
                $this->database->table('customer_addresses')
                    ->where('user_id', $userId)
                    ->where('type', $address->type)
                    ->update(['is_default' => false, 'updated_at' => now()]);
            }

            $values = [
                'user_id' => $userId,
                'type' => $address->type,
                'label' => trim($address->label),
                'postal_code' => preg_replace('/\D+/', '', $address->postalCode),
                'street' => trim($address->street),
                'number' => trim($address->number),
                'city' => trim($address->city),
                'state' => strtoupper(trim($address->state)),
                'is_default' => $isDefault,
                'updated_at' => now(),
            ];

            if ($existing === null) {
                $this->database->table('customer_addresses')->insert(['id' => $id, 'created_at' => now(), ...$values]);
            } else {
                $this->database->table('customer_addresses')->where('id', $id)->where('user_id', $userId)->update($values);
            }

            if ($existing !== null && (bool) $existing->is_default && (string) $existing->type !== $address->type) {
                $this->promoteOldestAddress($userId, (string) $existing->type);
            }

            return $id;
        }, 3);
    }

    public function deleteAddress(int $userId, string $addressId): void
    {
        $this->database->transaction(function () use ($userId, $addressId): void {
            $this->lockCustomer($userId);
            $address = $this->database->table('customer_addresses')
                ->where('id', $addressId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($address === null) {
                throw new DomainException('Endereco nao encontrado para esta conta.');
            }

            $this->database->table('customer_addresses')->where('id', $addressId)->where('user_id', $userId)->delete();

            if ((bool) $address->is_default) {
                $this->promoteOldestAddress($userId, (string) $address->type);
            }
        }, 3);
    }

    private function lockCustomer(int $userId): void
    {
        if ($this->database->table('users')->where('id', $userId)->lockForUpdate()->first() === null) {
            throw new DomainException('Cliente nao encontrado.');
        }
    }

    private function promoteOldestAddress(int $userId, string $type): void
    {
        $replacementId = $this->database->table('customer_addresses')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->oldest('created_at')
            ->value('id');
        if ($replacementId !== null) {
            $this->database->table('customer_addresses')->where('id', $replacementId)->update(['is_default' => true, 'updated_at' => now()]);
        }
    }
}
