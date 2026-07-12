<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Sku
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '' || strlen($normalized) > 64) {
            throw new InvalidArgumentException('SKU must contain between 1 and 64 characters.');
        }
        $this->value = $normalized;
    }
}
