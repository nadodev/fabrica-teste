<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Command;

use App\Modules\Cart\Domain\Exception\CartConcurrencyConflict;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Inventory\Application\Port\StockGateway;
use RuntimeException;

final readonly class UpdateCartItemQuantity
{
    public function __construct(private CartRepository $carts, private StockGateway $stock) {}

    public function handle(string $plainToken, string $cartItemKey, int $quantity): void
    {
        $tokenHash = hash('sha256', $plainToken);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $cart = $this->carts->findByTokenHash($tokenHash);

            if ($cart === null) {
                return;
            }

            $item = collect($cart->items())->first(fn ($row) => $row->cartItemKey === $cartItemKey);

            if ($item === null) {
                return;
            }

            if ($quantity > 0 && $this->stock->tracked($item->productId, $item->variationKey) && $this->stock->available($item->productId, $item->variationKey) < $quantity) {
                throw new RuntimeException('Estoque insuficiente para essa quantidade.');
            }

            $cart->updateQuantity($cartItemKey, $quantity);

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
