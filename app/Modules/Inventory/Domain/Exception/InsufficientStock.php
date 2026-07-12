<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Exception;

use DomainException;

final class InsufficientStock extends DomainException
{
    public static function forProduct(string $productId, int $requested, int $available): self
    {
        return new self("Insufficient stock for product {$productId}: requested {$requested}, available {$available}.");
    }
}
