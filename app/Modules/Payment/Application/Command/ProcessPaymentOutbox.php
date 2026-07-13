<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Shared\Application\Port\OutboxQueue;
use Throwable;

final readonly class ProcessPaymentOutbox
{
    public function __construct(private OutboxQueue $outbox, private ProcessPayment $processor) {}

    public function handle(int $limit = 50): int
    {
        $processed = 0;
        for ($handled = 0; $handled < max(1, $limit); $handled++) {
            $message = $this->outbox->claim('payment.requested');
            if ($message === null) {
                break;
            }

            try {
                $orderId = $message->payload['orderId'] ?? null;
                if (! is_string($orderId) || $orderId === '') {
                    throw new \RuntimeException('Payment message does not contain a valid order ID.');
                }
                $this->processor->handle($orderId);
                $this->outbox->markProcessed($message->id);
                $processed++;
            } catch (Throwable $exception) {
                $this->outbox->retry($message->id, $exception->getMessage());
            }
        }

        return $processed;
    }
}
