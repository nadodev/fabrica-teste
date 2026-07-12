<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Port;

use App\Modules\Cart\Domain\Cart;

interface CartRepository
{
    public function find(string $id): ?Cart;

    public function findByTokenHash(string $tokenHash): ?Cart;

    public function save(Cart $cart): void;
}
