<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain;

enum OrderStatus: string
{
    case QuoteRequested = 'quote_requested';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /** @return list<self> */
    public function allowedAdministrativeTransitions(): array
    {
        return match ($this) {
            self::QuoteRequested, self::AwaitingPayment => [],
            self::Paid => [self::Processing],
            self::Processing => [self::Shipped],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled, self::Refunded => [],
        };
    }
}
