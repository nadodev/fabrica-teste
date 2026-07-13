<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Query;

use App\Modules\Ordering\Application\Port\CustomerOrderReadModel;

final readonly class ListCustomerOrders
{
    public function __construct(private CustomerOrderReadModel $orders) {}

    /** @return list<array{id: string, number: string, status: string, checkoutType: string, totalAmount: int, currency: string, paymentMethod: string|null, paymentStatus: string|null, createdAt: string}> */
    public function handle(int $userId): array
    {
        return $this->orders->forUser($userId);
    }
}
