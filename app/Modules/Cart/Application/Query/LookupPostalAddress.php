<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Query;

use App\Modules\Cart\Application\DTO\PostalAddress;
use App\Modules\Cart\Application\Port\PostalAddressLookup;
use InvalidArgumentException;

final readonly class LookupPostalAddress
{
    public function __construct(private PostalAddressLookup $lookup) {}

    public function handle(string $postalCode): ?PostalAddress
    {
        $postalCode = preg_replace('/\D+/', '', $postalCode) ?? '';
        if (preg_match('/^\d{8}$/', $postalCode) !== 1) {
            throw new InvalidArgumentException('Informe um CEP valido com 8 digitos.');
        }

        return $this->lookup->find($postalCode);
    }
}
