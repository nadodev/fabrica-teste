<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class AdminReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = DB::table('ordering_orders')->whereBetween('created_at', [$from, $to]);
        $revenue = (int) (clone $orders)->sum('total_amount');
        $ordersCount = (int) (clone $orders)->count();

        return Inertia::render('admin/reports', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => [
                'revenue' => $revenue,
                'ordersCount' => $ordersCount,
                'averageTicket' => $ordersCount > 0 ? intdiv($revenue, $ordersCount) : 0,
                'abandonedCarts' => DB::table('cart_carts')
                    ->where('status', 'active')
                    ->where('updated_at', '<', now()->subHours(2))
                    ->count(),
                'criticalStock' => count($this->criticalStock()),
            ],
            'salesByDay' => (clone $orders)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as total_amount')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'topProducts' => DB::table('ordering_order_items')
                ->join('ordering_orders', 'ordering_orders.id', '=', 'ordering_order_items.order_id')
                ->whereBetween('ordering_orders.created_at', [$from, $to])
                ->select('ordering_order_items.name', 'ordering_order_items.sku', DB::raw('SUM(ordering_order_items.quantity) as quantity'), DB::raw('SUM(ordering_order_items.subtotal_amount) as total_amount'))
                ->groupBy('ordering_order_items.name', 'ordering_order_items.sku')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get(),
            'coupons' => DB::table('ordering_orders')
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('coupon_code')
                ->select('coupon_code', DB::raw('COUNT(*) as uses_count'), DB::raw('SUM(discount_amount) as discount_amount'), DB::raw('SUM(total_amount) as total_amount'))
                ->groupBy('coupon_code')
                ->orderByDesc('uses_count')
                ->get(),
            'abandonedCarts' => DB::table('cart_carts')
                ->leftJoin('cart_items', 'cart_items.cart_id', '=', 'cart_carts.id')
                ->where('cart_carts.status', 'active')
                ->where('cart_carts.updated_at', '<', now()->subHours(2))
                ->select('cart_carts.id', 'cart_carts.updated_at', DB::raw('COUNT(cart_items.id) as items_count'), DB::raw('COALESCE(SUM(cart_items.quantity), 0) as units_count'))
                ->groupBy('cart_carts.id', 'cart_carts.updated_at')
                ->orderByDesc('cart_carts.updated_at')
                ->limit(20)
                ->get(),
            'criticalStock' => $this->criticalStock(),
        ]);
    }

    /** @return list<array{name: string, sku: string, variation: string, stock: int, threshold: int}> */
    private function criticalStock(): array
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
