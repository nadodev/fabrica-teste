<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Command;

use App\Modules\Ordering\Application\Port\OrderNotificationGateway;
use App\Modules\Shared\Application\Port\OutboxQueue;
use Throwable;

final readonly class ProcessOrderOutbox
{
    public function __construct(private OutboxQueue $outbox, private OrderNotificationGateway $notifications) {}

    public function handle(int $limit = 50): int
    {
        $processed = 0;
        $handled = 0;

        while ($handled < max(1, $limit)) {
            $message = $this->outbox->claim('ordering.order_placed');
            if ($message === null) {
                break;
            }

            $handled++;

            try {
                $orderId = $message->payload['orderId'] ?? null;
                if (! is_string($orderId) || $orderId === '') {
                    throw new \RuntimeException('Outbox message does not contain a valid order ID.');
                }

                $this->notifications->sendPlaced($orderId);
                $this->outbox->markProcessed($message->id);
                $processed++;
            } catch (Throwable $exception) {
                $this->outbox->retry($message->id, $exception->getMessage());
            }
        }

        return $processed;
    }
}
