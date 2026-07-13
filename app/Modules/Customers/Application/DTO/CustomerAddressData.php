<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\DTO;

final readonly class CustomerAddressData
{
    public function __construct(
        public string $type,
        public string $label,
        public string $postalCode,
        public string $street,
        public string $number,
        public string $city,
        public string $state,
        public bool $isDefault,
    ) {}
}
