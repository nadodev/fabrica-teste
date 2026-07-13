<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Port;

use App\Modules\Cart\Domain\Cart;

interface CartRepository
{
    public function find(string $id): ?Cart;

    public function findByTokenHash(string $tokenHash, bool $onlyActive = true): ?Cart;

    public function save(Cart $cart): void;

    public function markConverted(Cart $cart): void;

    public function restoreAfterFailedCheckout(string $cartId): void;
}
