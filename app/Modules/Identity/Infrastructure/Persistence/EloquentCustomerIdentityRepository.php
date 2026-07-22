<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Models\User;
use App\Modules\Identity\Application\DTO\RegisterCustomerData;
use App\Modules\Identity\Application\DTO\RegisteredCustomer;
use App\Modules\Identity\Application\Port\CustomerIdentityRepository;
use Illuminate\Auth\Events\Verified;

final class EloquentCustomerIdentityRepository implements CustomerIdentityRepository
{
    public function create(RegisterCustomerData $data): RegisteredCustomer
    {
        $customer = User::query()->create([
            'name' => $data->name,
            'email' => mb_strtolower($data->email),
            'password' => $data->password,
        ]);

        return new RegisteredCustomer($customer->id, $customer->name, $customer->email);
    }

    public function markEmailVerified(int $customerId): bool
    {
        $customer = User::query()->findOrFail($customerId);
        if ($customer->hasVerifiedEmail()) {
            return false;
        }

        $customer->markEmailAsVerified();
        event(new Verified($customer));

        return true;
    }
}
