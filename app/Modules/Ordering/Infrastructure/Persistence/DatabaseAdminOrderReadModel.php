<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence;

use App\Modules\Ordering\Application\Port\AdminOrderReadModel;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseAdminOrderReadModel implements AdminOrderReadModel
{
    public function __construct(private ConnectionInterface $database) {}

    public function all(): array
    {
        $orders = $this->database->table('ordering_orders')->orderByDesc('created_at')->get()
            ->map(fn (object $order): array => [
                'id' => (string) $order->id,
                'number' => (string) $order->number,
                'customerName' => (string) ($order->customer_name ?? 'Cliente nao informado'),
                'customerEmail' => $order->customer_email,
                'customerPhone' => $order->customer_phone,
                'status' => (string) $order->status,
                'checkoutType' => (string) ($order->checkout_type ?? 'payment'),
                'subtotalAmount' => (int) ($order->subtotal_amount ?? $order->total_amount),
                'discountAmount' => (int) ($order->discount_amount ?? 0),
                'shippingAmount' => (int) ($order->shipping_amount ?? 0),
                'totalAmount' => (int) $order->total_amount,
                'currency' => (string) $order->currency,
                'couponCode' => $order->coupon_code,
                'paymentMethod' => $order->payment_method ?? null,
                'paymentStatus' => $order->payment_status ?? null,
                'createdAt' => (string) $order->created_at,
            ])->all();

        return array_values($orders);
    }

    public function find(string $orderId): ?array
    {
        $record = $this->database->table('ordering_orders')->where('id', $orderId)->first();
        if ($record === null) {
            return null;
        }
        $items = $this->database->table('ordering_order_items')->where('order_id', $orderId)->orderBy('id')->get()
            ->map(fn (object $item): array => [
                'productId' => (string) $item->product_id,
                'sku' => (string) $item->sku,
                'name' => (string) $item->name,
                'variationLabel' => $item->variation_label,
                'notes' => $item->notes,
                'unitPriceAmount' => (int) $item->unit_price_amount,
                'priceCurrency' => (string) $item->price_currency,
                'quantity' => (int) $item->quantity,
                'subtotalAmount' => (int) $item->subtotal_amount,
            ])->values()->all();

        return [
            'id' => (string) $record->id,
            'number' => (string) $record->number,
            'status' => (string) $record->status,
            'checkoutType' => (string) ($record->checkout_type ?? 'payment'),
            'customerName' => $record->customer_name,
            'customerEmail' => $record->customer_email,
            'customerPhone' => $record->customer_phone,
            'customerDocument' => $record->customer_document,
            'shippingZip' => $record->shipping_zip,
            'shippingAddress' => $record->shipping_address,
            'shippingNumber' => $record->shipping_number,
            'shippingCity' => $record->shipping_city,
            'shippingState' => $record->shipping_state,
            'deliveryMethod' => $record->delivery_method ?? 'shipping',
            'shippingService' => $record->shipping_service ?? null,
            'shippingCompany' => $record->shipping_company ?? null,
            'shippingAmount' => (int) ($record->shipping_amount ?? 0),
            'shippingDeliveryTime' => $record->shipping_delivery_time ?? null,
            'paymentMethod' => $record->payment_method ?? null,
            'paymentStatus' => $record->payment_status ?? null,
            'notes' => $record->notes,
            'subtotalAmount' => (int) ($record->subtotal_amount ?? $record->total_amount),
            'discountAmount' => (int) ($record->discount_amount ?? 0),
            'totalAmount' => (int) $record->total_amount,
            'currency' => (string) $record->currency,
            'couponCode' => $record->coupon_code,
            'createdAt' => (string) $record->created_at,
            'items' => $items,
        ];
    }

    public function statusHistory(string $orderId): array
    {
        $history = $this->database->table('ordering_order_status_history')
            ->leftJoin('users', 'users.id', '=', 'ordering_order_status_history.admin_user_id')
            ->where('order_id', $orderId)
            ->orderByDesc('ordering_order_status_history.created_at')
            ->get([
                'ordering_order_status_history.from_status as fromStatus',
                'ordering_order_status_history.to_status as toStatus',
                'ordering_order_status_history.note',
                'ordering_order_status_history.created_at as createdAt',
                'users.name as adminName',
            ])->map(fn (object $entry): array => (array) $entry)->all();

        return array_values($history);
    }
}
