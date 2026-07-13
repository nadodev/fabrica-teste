<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Port;

use App\Modules\Ordering\Application\ValueObject\CouponDiscount;

interface CouponGateway
{
    public function consume(string $code, int $subtotalAmount): CouponDiscount;
}
