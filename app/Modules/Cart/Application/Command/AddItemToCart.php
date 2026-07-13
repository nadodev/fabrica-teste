<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Command;

use App\Modules\Cart\Application\DTO\CartView;
use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\Exception\CartConcurrencyConflict;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Support\StoreSettings;
use RuntimeException;

final readonly class AddItemToCart
{
    public function __construct(private CartRepository $carts, private ProductRepository $products, private StockGateway $stock, private StoreSettings $settings) {}

    public function handle(string $cartId, string $plainToken, string $productId, int $quantity, ?string $variationId = null): CartView
    {
        $product = $this->products->find(ProductId::fromString($productId))
            ?? throw new RuntimeException('Product not found.');
        $tokenHash = hash('sha256', $plainToken);
        $allowOutOfStock = $this->settings->allowsOutOfStockSales();
        $variationLabel = $product->variationLabel($variationId);
        $variationKey = $variationLabel === null ? null : (string) $variationId;
        $sku = $product->variationSku($variationKey);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $cart = $this->carts->findByTokenHash($tokenHash) ?? new Cart($cartId, $tokenHash, $product->price()->currency);
            $requestedTotal = $cart->quantityFor($product->id->value, $variationKey) + $quantity;
            $available = $this->stock->available($product->id->value, $variationKey);

            if (! $allowOutOfStock && $this->settings->controlsStock() && $this->stock->tracked($product->id->value, $variationKey) && $available < $requestedTotal) {
                throw new RuntimeException("Estoque insuficiente. Disponivel: {$available}.");
            }

            $cart->add($product->id->value, $product->name(), $product->price(), $quantity, $sku, $product->imageUrl(), $variationKey, $variationLabel);

            try {
                $this->carts->save($cart);

                return CartView::fromDomain($cart);
            } catch (CartConcurrencyConflict) {
                if ($attempt === 3) {
                    throw new CartConcurrencyConflict('Cart remained busy after three attempts.');
                }
            }
        }

        throw new CartConcurrencyConflict('Could not update cart.');
    }
}
