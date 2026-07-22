<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Port;

interface CustomerOrderReadModel
{
    /** @return list<array{id: string, number: string, status: string, checkoutType: string, totalAmount: int, currency: string, paymentMethod: string|null, paymentStatus: string|null, createdAt: string}> */
    public function forUser(int $userId): array;

    /** @return array<string, mixed>|null */
    public function findForUser(string $orderId, int $userId): ?array;
}
