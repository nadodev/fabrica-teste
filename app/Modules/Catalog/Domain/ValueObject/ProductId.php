<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ProductId
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException('Invalid product UUID.');
        }

        return new self(strtolower($value));
    }
}
