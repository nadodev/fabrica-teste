<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTO;

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartItem;

final readonly class CartView
{
    /** @param list<array<string, int|string|null>> $items */
    public function __construct(
        public array $items,
        public int $subtotalAmount,
        public int $discountAmount,
        public int $totalAmount,
        public string $currency,
        public ?array $coupon = null,
    ) {}

    public static function empty(): self
    {
        return new self([], 0, 0, 0, 'BRL');
    }

    public static function fromDomain(Cart $cart, ?array $coupon = null): self
    {
        $subtotal = $cart->total()->amount;
        $discount = min($subtotal, (int) ($coupon['discountAmount'] ?? 0));

        return new self(
            array_map(fn (CartItem $item): array => [
                'productId' => $item->productId,
                'cartItemKey' => $item->cartItemKey,
                'sku' => $item->sku,
                'name' => $item->name,
                'unitPriceAmount' => $item->unitPrice->amount,
                'priceCurrency' => $item->unitPrice->currency,
                'quantity' => $item->quantity,
                'subtotalAmount' => $item->subtotal()->amount,
                'imageUrl' => $item->imageUrl,
                'variationKey' => $item->variationKey,
                'variationLabel' => $item->variationLabel,
            ], $cart->items()),
            $subtotal,
            $discount,
            max(0, $subtotal - $discount),
            $cart->currency,
            $coupon === null ? null : [
                'code' => $coupon['code'],
                'description' => $coupon['description'] ?? '',
                'discountAmount' => $discount,
            ],
        );
    }
}
