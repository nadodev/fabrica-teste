<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\ValueObject;

final readonly class CouponDiscount
{
    public function __construct(public string $code, public int $amount) {}
}
