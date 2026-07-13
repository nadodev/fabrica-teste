<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Chargeback = 'chargeback';
}
