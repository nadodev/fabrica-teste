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
}
