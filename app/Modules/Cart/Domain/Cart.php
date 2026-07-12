<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain;

use App\Modules\Shared\Domain\ValueObject\Money;
use InvalidArgumentException;

final class Cart
{
    /** @var array<string, CartItem> */
    private array $items = [];

    public function __construct(
        public readonly string $id,
        public readonly string $tokenHash = '',
        public readonly string $currency = 'BRL',
        private int $version = 0,
    ) {}

    /** @param list<CartItem> $items */
    public static function restore(string $id, string $tokenHash, string $currency, int $version, array $items): self
    {
        $cart = new self($id, $tokenHash, $currency, $version);

        foreach ($items as $item) {
            $cart->items[$item->productId] = $item;
        }

        return $cart;
    }

    public function add(string $productId, string $name, Money $unitPrice, int $quantity = 1, string $sku = '', ?string $imageUrl = null): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Cart quantity must be at least one.');
        }

        $current = $this->items[$productId] ?? null;
        $newQuantity = $current === null ? $quantity : $current->quantity + $quantity;
        $this->items[$productId] = new CartItem($productId, $name, $unitPrice, $newQuantity, $sku, $imageUrl);
    }

    /** @return list<CartItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function remove(string $productId): void
    {
        unset($this->items[$productId]);
    }

    public function version(): int
    {
        return $this->version;
    }

    public function markPersisted(): void
    {
        $this->version++;
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
