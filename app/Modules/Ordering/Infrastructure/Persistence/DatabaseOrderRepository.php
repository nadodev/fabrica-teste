<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderDetails;
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
        return $this->hydrate($this->database->table('ordering_orders')->where('id', $id)->first());
    }

    public function findForUpdate(string $id): ?Order
    {
        return $this->hydrate($this->database->table('ordering_orders')->where('id', $id)->lockForUpdate()->first());
    }

    private function hydrate(?object $record): ?Order
    {

        if ($record === null) {
            return null;
        }

        $row = (array) $record;
        $items = $this->database->table('ordering_order_items')->where('order_id', $row['id'])->orderBy('id')->get()
            ->map(fn (object $item): OrderItem => new OrderItem(
                (string) $item->product_id,
                (string) $item->sku,
                (string) $item->name,
                new Money((int) $item->unit_price_amount, (string) $item->price_currency),
                (int) $item->quantity,
                $item->variation_key === null ? null : (string) $item->variation_key,
                $item->variation_label === null ? null : (string) $item->variation_label,
                $item->notes === null ? null : (string) $item->notes,
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
            new OrderDetails(
                (string) ($row['checkout_type'] ?? 'payment'),
                (string) ($row['customer_name'] ?? ''),
                (string) ($row['customer_email'] ?? ''),
                (string) ($row['customer_phone'] ?? ''),
                $row['customer_document'] === null ? null : (string) $row['customer_document'],
                (string) ($row['shipping_zip'] ?? ''),
                (string) ($row['shipping_address'] ?? ''),
                (string) ($row['shipping_number'] ?? ''),
                (string) ($row['shipping_city'] ?? ''),
                (string) ($row['shipping_state'] ?? ''),
                (string) ($row['delivery_method'] ?? 'shipping'),
                $row['shipping_service'] === null ? null : (string) $row['shipping_service'],
                $row['shipping_company'] === null ? null : (string) $row['shipping_company'],
                new Money((int) ($row['shipping_amount'] ?? 0), (string) $row['currency']),
                $row['shipping_delivery_time'] === null ? null : (int) $row['shipping_delivery_time'],
                (string) ($row['payment_method'] ?? 'pix'),
                (string) ($row['payment_status'] ?? 'pending'),
                $row['notes'] === null ? null : (string) $row['notes'],
                $row['coupon_code'] === null ? null : (string) $row['coupon_code'],
                new Money((int) ($row['discount_amount'] ?? 0), (string) $row['currency']),
            ),
            $row['customer_user_id'] === null ? null : (int) $row['customer_user_id'],
        );
    }

    public function findByCartId(string $cartId): ?Order
    {
        $id = $this->database->table('ordering_orders')->where('cart_id', $cartId)->value('id');

        return $id === null ? null : $this->find((string) $id);
    }

    public function findByNumber(string $number): ?Order
    {
        $id = $this->database->table('ordering_orders')->where('number', $number)->value('id');

        return $id === null ? null : $this->find((string) $id);
    }

    public function save(Order $order): void
    {
        $this->database->transaction(function () use ($order): void {
            $details = $order->details();
            $exists = $this->database->table('ordering_orders')->where('id', $order->id)->exists();
            $values = [
                'number' => $order->number,
                'cart_id' => $order->cartId,
                'status' => $order->status()->value,
                'checkout_type' => $details->checkoutType,
                'customer_name' => $details->customerName,
                'customer_user_id' => $order->customerUserId,
                'customer_email' => $details->customerEmail,
                'customer_phone' => $details->customerPhone,
                'customer_document' => $details->customerDocument,
                'shipping_zip' => $details->shippingZip,
                'shipping_address' => $details->shippingAddress,
                'shipping_number' => $details->shippingNumber,
                'shipping_city' => $details->shippingCity,
                'shipping_state' => $details->shippingState,
                'delivery_method' => $details->deliveryMethod,
                'shipping_service' => $details->shippingService,
                'shipping_company' => $details->shippingCompany,
                'shipping_amount' => $details->shipping->amount,
                'shipping_delivery_time' => $details->shippingDeliveryTime,
                'payment_method' => $details->paymentMethod,
                'payment_status' => $details->paymentStatus,
                'notes' => $details->notes,
                'subtotal_amount' => $order->subtotal()->amount,
                'discount_amount' => $details->discount->amount,
                'coupon_code' => $details->couponCode,
                'total_amount' => $order->total()->amount,
                'currency' => $order->total()->currency,
                'updated_at' => now(),
            ];

            if ($exists) {
                $this->database->table('ordering_orders')->where('id', $order->id)->update($values);

                return;
            }

            $this->database->table('ordering_orders')->insert(['id' => $order->id, ...$values, 'created_at' => now()]);

            foreach ($order->items() as $item) {
                $this->database->table('ordering_order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item->productId,
                    'variation_key' => $item->variationKey,
                    'variation_label' => $item->variationLabel,
                    'notes' => $item->notes,
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
