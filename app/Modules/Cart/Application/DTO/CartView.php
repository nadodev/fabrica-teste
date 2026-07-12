<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTO;

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartItem;

final readonly class CartView
{
    /** @param list<array<string, int|string|null>> $items */
    public function __construct(public array $items, public int $totalAmount, public string $currency) {}

    public static function empty(): self
    {
        return new self([], 0, 'BRL');
    }

    public static function fromDomain(Cart $cart): self
    {
        return new self(
            array_map(fn (CartItem $item): array => [
                'productId' => $item->productId,
                'sku' => $item->sku,
                'name' => $item->name,
                'unitPriceAmount' => $item->unitPrice->amount,
                'priceCurrency' => $item->unitPrice->currency,
                'quantity' => $item->quantity,
                'subtotalAmount' => $item->subtotal()->amount,
                'imageUrl' => $item->imageUrl,
            ], $cart->items()),
            $cart->total()->amount,
            $cart->currency,
        );
    }
}
