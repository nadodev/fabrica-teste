<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Query;

use App\Modules\Ordering\Application\Port\CustomerOrderReadModel;

final readonly class ShowCustomerOrder
{
    public function __construct(private CustomerOrderReadModel $orders) {}

    /** @return array<string, mixed>|null */
    public function handle(string $orderId, int $userId): ?array
    {
        return $this->orders->findForUser($orderId, $userId);
    }
}
