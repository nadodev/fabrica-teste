<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTO;

final readonly class PostalAddress
{
    public function __construct(
        public string $postalCode,
        public string $street,
        public string $neighborhood,
        public string $city,
        public string $state,
    ) {}

    /** @return array{postalCode: string, street: string, neighborhood: string, city: string, state: string} */
    public function toArray(): array
    {
        return [
            'postalCode' => $this->postalCode,
            'street' => $this->street,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
        ];
    }
}
