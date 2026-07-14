<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Port;

use App\Modules\Ordering\Domain\OrderStatus;

interface OrderStatusHistoryRecorder
{
    public function record(string $orderId, OrderStatus $from, OrderStatus $to, ?int $adminUserId, ?string $note): void;
}
