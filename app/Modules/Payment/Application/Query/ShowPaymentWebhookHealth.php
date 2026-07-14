<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query;

use App\Modules\Payment\Application\Port\PaymentWebhookInbox;

final readonly class ShowPaymentWebhookHealth
{
    public function __construct(private PaymentWebhookInbox $inbox) {}

    /** @return array{pending: int, processing: int, failed: int} */
    public function handle(): array
    {
        return $this->inbox->statusCounts();
    }
}
