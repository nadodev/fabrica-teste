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
        private OrderDetails $details,
        public readonly ?int $customerUserId = null,
    ) {}

    /** @param list<OrderItem> $items */
    public static function place(string $id, string $number, string $cartId, array $items, OrderDetails $details, ?int $customerUserId = null): self
    {
        if ($items === []) {
            throw new DomainException('Cannot place an order without items.');
        }

        $status = $details->checkoutType === 'quote' ? OrderStatus::QuoteRequested : OrderStatus::AwaitingPayment;

        return new self($id, $number, $cartId, $items, $status, $details, $customerUserId);
    }

    /** @param non-empty-list<OrderItem> $items */
    public static function restore(string $id, string $number, string $cartId, array $items, OrderStatus $status, OrderDetails $details, ?int $customerUserId = null): self
    {
        return new self($id, $number, $cartId, $items, $status, $details, $customerUserId);
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

    public function details(): OrderDetails
    {
        return $this->details;
    }

    public function subtotal(): Money
    {
        $subtotal = new Money(0, $this->items[0]->unitPrice->currency);

        foreach ($this->items as $item) {
            $subtotal = $subtotal->add($item->subtotal());
        }

        return $subtotal;
    }

    public function total(): Money
    {
        $subtotal = $this->subtotal();
        if ($this->details->discount->amount > $subtotal->amount) {
            throw new DomainException('Order discount cannot exceed subtotal.');
        }

        return new Money(
            $subtotal->amount - $this->details->discount->amount + $this->details->shipping->amount,
            $subtotal->currency,
        );
    }

    public function markPaid(): void
    {
        if ($this->status !== OrderStatus::AwaitingPayment) {
            throw new DomainException('Only an awaiting payment order can be paid.');
        }

        $this->status = OrderStatus::Paid;
        $this->details = $this->details->withPaymentStatus('paid');
    }

    public function cancelAfterPaymentFailure(): void
    {
        if ($this->status !== OrderStatus::AwaitingPayment) {
            throw new DomainException('Only an awaiting payment order can be cancelled after payment failure.');
        }

        $this->status = OrderStatus::Cancelled;
        $this->details = $this->details->withPaymentStatus('refused');
    }

    public function markRefunded(): void
    {
        if (! in_array($this->status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw new DomainException('Only a paid order can be refunded.');
        }

        $this->status = OrderStatus::Refunded;
        $this->details = $this->details->withPaymentStatus('refunded');
    }

    public function recordPaymentStatus(string $status): void
    {
        $this->details = $this->details->withPaymentStatus($status);
    }

    public function changeAdministrativeStatus(OrderStatus $next): void
    {
        if ($next === $this->status) {
            return;
        }

        if (! in_array($next, $this->status->allowedAdministrativeTransitions(), true)) {
            throw new DomainException("Order status cannot change from {$this->status->value} to {$next->value}.");
        }

        $this->status = $next;
    }
}
