<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Port;

use App\Modules\Cart\Application\DTO\PostalAddress;

interface PostalAddressLookup
{
    public function find(string $postalCode): ?PostalAddress;
}
