<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain;

use App\Modules\Shared\Domain\ValueObject\Money;
use DomainException;

final class Order
{
    /** @param non-empty-list<OrderItem> $items */
    private function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $cartId,
        private array $items,
        private OrderStatus $status,
    ) {}

    /** @param list<OrderItem> $items */
    public static function place(string $id, string $number, string $cartId, array $items): self
    {
        if ($items === []) {
            throw new DomainException('Cannot place an order without items.');
        }

        return new self($id, $number, $cartId, $items, OrderStatus::AwaitingPayment);
    }

    /** @param non-empty-list<OrderItem> $items */
    public static function restore(string $id, string $number, string $cartId, array $items, OrderStatus $status): self
    {
        return new self($id, $number, $cartId, $items, $status);
    }

    /** @return non-empty-list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function total(): Money
    {
        $total = new Money(0, $this->items[0]->unitPrice->currency);

        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }

    public function markPaid(): void
    {
        if ($this->status !== OrderStatus::AwaitingPayment) {
            throw new DomainException('Only an awaiting payment order can be paid.');
        }

        $this->status = OrderStatus::Paid;
    }
}
