<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Command;

use App\Modules\Cart\Domain\Exception\CartConcurrencyConflict;
use App\Modules\Cart\Domain\Port\CartRepository;

final readonly class UpdateCartItemNotes
{
    public function __construct(private CartRepository $carts) {}

    public function handle(string $plainToken, string $cartItemKey, ?string $notes): void
    {
        $tokenHash = hash('sha256', $plainToken);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $cart = $this->carts->findByTokenHash($tokenHash);

            if ($cart === null) {
                return;
            }

            $cart->updateNotes($cartItemKey, $notes === null ? null : trim($notes));

            try {
                $this->carts->save($cart);

                return;
            } catch (CartConcurrencyConflict) {
                if ($attempt === 3) {
                    throw new CartConcurrencyConflict('Cart remained busy after three attempts.');
                }
            }
        }
    }
}
