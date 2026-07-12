<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Command;

use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderItem;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;
use Ramsey\Uuid\Uuid;

final readonly class CheckoutCart
{
    public function __construct(
        private CartRepository $carts,
        private OrderRepository $orders,
        private StockGateway $stock,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $orderId, string $plainCartToken): Order
    {
        return $this->transactions->run(function () use ($orderId, $plainCartToken): Order {
            $cart = $this->carts->findByTokenHash(hash('sha256', $plainCartToken), false)
                ?? throw new DomainException('Active cart not found.');
            $existing = $this->orders->findByCartId($cart->id);

            if ($existing !== null) {
                return $existing;
            }

            $items = array_map(
                fn ($item): OrderItem => new OrderItem($item->productId, $item->sku, $item->name, $item->unitPrice, $item->quantity, $item->variationKey, $item->variationLabel),
                $cart->items(),
            );
            $order = Order::place($orderId, $this->orders->nextIdentity(), $cart->id, $items);

            foreach ($order->items() as $item) {
                $reservationId = Uuid::uuid5(Uuid::NAMESPACE_URL, $order->id.':'.$item->productId.':'.($item->variationKey ?? 'default'))->toString();
                $this->stock->reserve($reservationId, $item->productId, $item->quantity);
            }

            $this->orders->save($order);
            $this->carts->markConverted($cart);

            return $order;
        });
    }
}
