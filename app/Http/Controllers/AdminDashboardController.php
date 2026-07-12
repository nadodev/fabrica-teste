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
        return DB::table('catalog_products')->whereNotNull('variations')->get(['name', 'sku', 'variations'])
            ->flatMap(function (object $product): array {
                $variations = json_decode((string) $product->variations, true);

                if (! is_array($variations)) {
                    return [];
                }

                return array_values(array_filter(array_map(function (array $variation) use ($product): ?array {
                    $stock = (int) ($variation['stock'] ?? 0);
                    $threshold = (int) ($variation['lowStockThreshold'] ?? 5);

                    if ($stock > $threshold) {
                        return null;
                    }

                    return [
                        'name' => (string) $product->name,
                        'sku' => (string) $product->sku,
                        'variation' => trim(($variation['name'] ?? '').': '.($variation['value'] ?? '')),
                        'stock' => $stock,
                        'threshold' => $threshold,
                    ];
                }, $variations)));
            })
            ->take(8)
            ->values()
            ->all();
    }
}
