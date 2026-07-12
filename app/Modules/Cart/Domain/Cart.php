<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain;

use App\Modules\Shared\Domain\ValueObject\Money;
use InvalidArgumentException;

final class Cart
{
    /** @var array<string, CartItem> */
    private array $items = [];

    public function __construct(public readonly string $id) {}

    public function add(string $productId, string $name, Money $unitPrice, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Cart quantity must be at least one.');
        }

        $current = $this->items[$productId] ?? null;
        $newQuantity = $current === null ? $quantity : $current->quantity + $quantity;
        $this->items[$productId] = new CartItem($productId, $name, $unitPrice, $newQuantity);
    }

    /** @return list<CartItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function total(): Money
    {
        $total = new Money(0);

        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }
}
