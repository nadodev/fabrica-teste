<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Modules\Ordering\Application\Port\CustomerOrderReadModel;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseCustomerOrderReadModel implements CustomerOrderReadModel
{
    public function __construct(private ConnectionInterface $database) {}

    public function forUser(int $userId): array
    {
        $orders = $this->database->table('ordering_orders')
            ->where('customer_user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $order): array => [
                'id' => (string) $order->id,
                'number' => (string) $order->number,
                'status' => (string) $order->status,
                'checkoutType' => (string) ($order->checkout_type ?? 'payment'),
                'totalAmount' => (int) $order->total_amount,
                'currency' => (string) $order->currency,
                'paymentMethod' => $order->payment_method === null ? null : (string) $order->payment_method,
                'paymentStatus' => $order->payment_status === null ? null : (string) $order->payment_status,
                'createdAt' => (string) $order->created_at,
            ])
            ->all();

        return array_values($orders);
    }
}
