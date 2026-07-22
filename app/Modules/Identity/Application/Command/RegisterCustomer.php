<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command;

use App\Modules\Identity\Application\DTO\RegisterCustomerData;
use App\Modules\Identity\Application\DTO\RegisteredCustomer;
use App\Modules\Identity\Application\Port\CustomerIdentityRepository;
use App\Modules\Identity\Application\Port\CustomerNotificationSender;

final readonly class RegisterCustomer
{
    public function __construct(
        private CustomerIdentityRepository $customers,
        private CustomerNotificationSender $notifications,
    ) {}

    public function handle(RegisterCustomerData $data): RegisteredCustomer
    {
        $customer = $this->customers->create($data);
        $this->notifications->sendEmailVerification($customer->id);

        return $customer;
    }
}
