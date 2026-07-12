<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderItem;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseOrderRepository implements OrderRepository
{
    public function __construct(private ConnectionInterface $database) {}

    public function nextIdentity(): string
    {
        return $this->database->transaction(function (): string {
            $this->database->table('ordering_counters')->insertOrIgnore(['name' => 'order', 'value' => 0]);
            $counter = $this->database->table('ordering_counters')->where('name', 'order')->lockForUpdate()->firstOrFail();
            $next = (int) $counter->value + 1;
            $this->database->table('ordering_counters')->where('name', 'order')->update(['value' => $next]);

            return 'PED-'.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
        }, 3);
    }

    public function find(string $id): ?Order
    {
        $record = $this->database->table('ordering_orders')->where('id', $id)->first();

        if ($record === null) {
            return null;
        }

        $row = (array) $record;
        $items = $this->database->table('ordering_order_items')->where('order_id', $id)->orderBy('id')->get()
            ->map(fn (object $item): OrderItem => new OrderItem(
                (string) $item->product_id,
                (string) $item->sku,
                (string) $item->name,
                new Money((int) $item->unit_price_amount, (string) $item->price_currency),
                (int) $item->quantity,
            ))->all();

        if ($items === []) {
            return null;
        }

        return Order::restore(
            (string) $row['id'],
            (string) $row['number'],
            (string) $row['cart_id'],
            array_values($items),
            OrderStatus::from((string) $row['status']),
        );
    }

    public function save(Order $order): void
    {
        $this->database->transaction(function () use ($order): void {
            $this->database->table('ordering_orders')->updateOrInsert(['id' => $order->id], [
                'number' => $order->number,
                'cart_id' => $order->cartId,
                'status' => $order->status()->value,
                'total_amount' => $order->total()->amount,
                'currency' => $order->total()->currency,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->database->table('ordering_order_items')->where('order_id', $order->id)->delete();

            foreach ($order->items() as $item) {
                $this->database->table('ordering_order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item->productId,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'unit_price_amount' => $item->unitPrice->amount,
                    'price_currency' => $item->unitPrice->currency,
                    'quantity' => $item->quantity,
                    'subtotal_amount' => $item->subtotal()->amount,
                ]);
            }
        }, 3);
    }
}
