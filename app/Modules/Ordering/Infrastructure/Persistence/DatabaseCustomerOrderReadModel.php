<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Modules\Ordering\Application\Port\CustomerOrderReadModel;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseCustomerOrderReadModel implements CustomerOrderReadModel
{
    public function __construct(private ConnectionInterface $database) {}

    public function forUser(int $userId): array
    {
        $orders = $this->database->table('ordering_orders')
            ->where('customer_user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $order): array => [
                'id' => (string) $order->id,
                'number' => (string) $order->number,
                'status' => (string) $order->status,
                'checkoutType' => (string) ($order->checkout_type ?? 'payment'),
                'totalAmount' => (int) $order->total_amount,
                'currency' => (string) $order->currency,
                'paymentMethod' => $order->payment_method === null ? null : (string) $order->payment_method,
                'paymentStatus' => $order->payment_status === null ? null : (string) $order->payment_status,
                'createdAt' => (string) $order->created_at,
            ])
            ->all();

        return array_values($orders);
    }

    public function findForUser(string $orderId, int $userId): ?array
    {
        $order = $this->database->table('ordering_orders')
            ->where('id', $orderId)
            ->where('customer_user_id', $userId)
            ->first();

        if ($order === null) {
            return null;
        }

        $items = $this->database->table('ordering_order_items')
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get()
            ->map(fn (object $item): array => [
                'productId' => (string) $item->product_id,
                'sku' => (string) $item->sku,
                'name' => (string) $item->name,
                'variationLabel' => $item->variation_label === null ? null : (string) $item->variation_label,
                'notes' => $item->notes === null ? null : (string) $item->notes,
                'unitPriceAmount' => (int) $item->unit_price_amount,
                'priceCurrency' => (string) $item->price_currency,
                'quantity' => (int) $item->quantity,
                'subtotalAmount' => (int) $item->subtotal_amount,
            ])
            ->values()
            ->all();

        return [
            'id' => (string) $order->id,
            'number' => (string) $order->number,
            'status' => (string) $order->status,
            'checkoutType' => (string) ($order->checkout_type ?? 'payment'),
            'customerName' => $order->customer_name === null ? null : (string) $order->customer_name,
            'customerEmail' => $order->customer_email === null ? null : (string) $order->customer_email,
            'customerPhone' => $order->customer_phone === null ? null : (string) $order->customer_phone,
            'shippingZip' => $order->shipping_zip === null ? null : (string) $order->shipping_zip,
            'shippingAddress' => $order->shipping_address === null ? null : (string) $order->shipping_address,
            'shippingNumber' => $order->shipping_number === null ? null : (string) $order->shipping_number,
            'shippingCity' => $order->shipping_city === null ? null : (string) $order->shipping_city,
            'shippingState' => $order->shipping_state === null ? null : (string) $order->shipping_state,
            'deliveryMethod' => (string) ($order->delivery_method ?? 'shipping'),
            'shippingService' => $order->shipping_service === null ? null : (string) $order->shipping_service,
            'shippingCompany' => $order->shipping_company === null ? null : (string) $order->shipping_company,
            'shippingAmount' => (int) ($order->shipping_amount ?? 0),
            'shippingDeliveryTime' => $order->shipping_delivery_time === null ? null : (int) $order->shipping_delivery_time,
            'paymentMethod' => $order->payment_method === null ? null : (string) $order->payment_method,
            'paymentStatus' => $order->payment_status === null ? null : (string) $order->payment_status,
            'notes' => $order->notes === null ? null : (string) $order->notes,
            'subtotalAmount' => (int) ($order->subtotal_amount ?? $order->total_amount),
            'discountAmount' => (int) ($order->discount_amount ?? 0),
            'totalAmount' => (int) $order->total_amount,
            'currency' => (string) $order->currency,
            'couponCode' => $order->coupon_code === null ? null : (string) $order->coupon_code,
            'createdAt' => (string) $order->created_at,
            'items' => $items,
        ];
    }
}
