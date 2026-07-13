<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $orders = DB::table('ordering_orders');
        $products = DB::table('catalog_products');
        $totalRevenue = (int) $orders->sum('total_amount');
        $orderCount = (int) DB::table('ordering_orders')->count();
        $todayRevenue = (int) DB::table('ordering_orders')->whereDate('created_at', today())->sum('total_amount');
        $pendingOrders = (int) DB::table('ordering_orders')->whereIn('status', ['awaiting_payment', 'quote_requested'])->count();
        $quoteCount = (int) DB::table('ordering_orders')->where('checkout_type', 'quote')->count();
        $activeProducts = (int) DB::table('catalog_products')->where('status', 'active')->count();
        $cartCount = (int) DB::table('cart_carts')->where('status', 'active')->count();
        $lowStock = $this->lowStockProducts();

        $recentOrders = DB::table('ordering_orders')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (object $order): array => [
                'id' => (string) $order->id,
                'number' => (string) $order->number,
                'customerName' => (string) ($order->customer_name ?? 'Cliente'),
                'totalAmount' => (int) $order->total_amount,
                'currency' => (string) $order->currency,
                'status' => (string) $order->status,
                'checkoutType' => (string) ($order->checkout_type ?? 'payment'),
                'createdAt' => (string) $order->created_at,
            ])
            ->all();

        $topProducts = DB::table('ordering_order_items')
            ->select('name', 'sku', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(subtotal_amount) as total_amount'))
            ->groupBy('name', 'sku')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn (object $item): array => [
                'name' => (string) $item->name,
                'sku' => (string) $item->sku,
                'quantity' => (int) $item->quantity,
                'totalAmount' => (int) $item->total_amount,
            ])
            ->all();

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'totalRevenue' => $totalRevenue,
                'orderCount' => $orderCount,
                'todayRevenue' => $todayRevenue,
                'pendingOrders' => $pendingOrders,
                'quoteCount' => $quoteCount,
                'activeProducts' => $activeProducts,
                'cartCount' => $cartCount,
                'averageTicket' => $orderCount > 0 ? intdiv($totalRevenue, $orderCount) : 0,
                'lowStockCount' => count($lowStock),
            ],
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'lowStock' => $lowStock,
        ]);
    }

    /** @return list<array{name: string, sku: string, variation: string, stock: int, threshold: int}> */
    private function lowStockProducts(): array
    {
        $levels = DB::table('inventory_stock_levels')
            ->join('catalog_products', 'catalog_products.id', '=', 'inventory_stock_levels.product_id')
            ->whereRaw('(inventory_stock_levels.on_hand - inventory_stock_levels.reserved) <= inventory_stock_levels.low_stock_threshold')
            ->get(['catalog_products.name', 'catalog_products.variations', 'inventory_stock_levels.sku', 'inventory_stock_levels.variation_key', 'inventory_stock_levels.on_hand', 'inventory_stock_levels.reserved', 'inventory_stock_levels.low_stock_threshold'])
            ->map(fn (object $level): array => [
                'name' => (string) $level->name,
                'sku' => (string) $level->sku,
                'variation' => $this->variationLabel($level->variations, $level->variation_key),
                'stock' => max(0, (int) $level->on_hand - (int) $level->reserved),
                'threshold' => (int) $level->low_stock_threshold,
            ])
            ->take(8)
            ->values()
            ->all();

        return array_values($levels);
    }

    private function variationLabel(mixed $encoded, mixed $variationKey): string
    {
        if ($variationKey === null) {
            return 'Produto simples';
        }

        $variations = json_decode((string) $encoded, true);
        foreach (is_array($variations) ? $variations : [] as $variation) {
            if (is_array($variation) && ($variation['id'] ?? null) === $variationKey) {
                return trim((string) ($variation['name'] ?? '').': '.(string) ($variation['value'] ?? ''));
            }
        }

        return (string) $variationKey;
    }
}
