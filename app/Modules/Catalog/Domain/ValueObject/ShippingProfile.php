<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ShippingProfile
{
    public function __construct(
        public int $weightGrams,
        public int $widthCentimeters,
        public int $heightCentimeters,
        public int $lengthCentimeters,
    ) {
        if ($weightGrams < 1 || $weightGrams > 30000) {
            throw new InvalidArgumentException('Product weight must contain between 1 and 30000 grams.');
        }

        foreach ([$widthCentimeters, $heightCentimeters, $lengthCentimeters] as $dimension) {
            if ($dimension < 1 || $dimension > 200) {
                throw new InvalidArgumentException('Product dimensions must contain between 1 and 200 centimeters.');
            }
        }
    }
}
