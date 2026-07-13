<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminOrderController extends Controller
{
    public function index(): Response
    {
        $orders = DB::table('ordering_orders')
            ->orderByDesc('created_at')
            ->get()
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
            ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(string $order): Response
    {
        $record = DB::table('ordering_orders')->where('id', $order)->first();
        abort_if($record === null, 404);

        $items = DB::table('ordering_order_items')
            ->where('order_id', $order)
            ->orderBy('id')
            ->get()
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
            ]);

        return Inertia::render('admin/orders/show', [
            'order' => [
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
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    public function updateStatus(string $order, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
        ]);

        DB::table('ordering_orders')->where('id', $order)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Status do pedido atualizado.');
    }

    /** @return array<string, string> */
    private function statuses(): array
    {
        return [
            'quote_requested' => 'Orcamento',
            'awaiting_payment' => 'Novo',
            'paid' => 'Pago',
            'processing' => 'Em producao',
            'shipped' => 'Enviado',
            'delivered' => 'Entregue',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];
    }
}
