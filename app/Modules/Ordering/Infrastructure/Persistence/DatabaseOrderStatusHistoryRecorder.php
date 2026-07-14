<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Modules\Ordering\Application\Port\OrderStatusHistoryRecorder;
use App\Modules\Ordering\Domain\OrderStatus;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;

final readonly class DatabaseOrderStatusHistoryRecorder implements OrderStatusHistoryRecorder
{
    public function __construct(private ConnectionInterface $database) {}

    public function record(string $orderId, OrderStatus $from, OrderStatus $to, ?int $adminUserId, ?string $note): void
    {
        $this->database->table('ordering_order_status_history')->insert([
            'id' => (string) Uuid::uuid4(),
            'order_id' => $orderId,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'admin_user_id' => $adminUserId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
