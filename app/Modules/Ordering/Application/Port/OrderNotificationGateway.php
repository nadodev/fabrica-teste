<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Port;

interface OrderNotificationGateway
{
    public function sendPlaced(string $orderId): void;
}
