<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Query;

use App\Modules\Cart\Application\DTO\CartView;
use App\Modules\Cart\Domain\Port\CartRepository;

final readonly class ShowCart
{
    public function __construct(private CartRepository $carts) {}

    public function handle(?string $plainToken): CartView
    {
        if ($plainToken === null) {
            return CartView::empty();
        }

        $cart = $this->carts->findByTokenHash(hash('sha256', $plainToken));

        return $cart === null ? CartView::empty() : CartView::fromDomain($cart);
    }
}
