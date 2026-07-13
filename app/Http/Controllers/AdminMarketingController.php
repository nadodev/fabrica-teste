<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class AdminMarketingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/marketing', [
            'stats' => [
                'activeBanners' => DB::table('site_banners')->where('is_active', true)->count(),
                'activeCoupons' => DB::table('commerce_coupons')->where('is_active', true)->count(),
                'activeNotifications' => DB::table('site_topbar_notifications')->where('is_active', true)->count(),
                'couponDiscounts' => (int) DB::table('ordering_orders')->sum('discount_amount'),
            ],
            'coupons' => DB::table('commerce_coupons')->orderByDesc('created_at')->limit(5)->get(),
            'banners' => DB::table('site_banners')->orderBy('sort_order')->orderByDesc('created_at')->limit(5)->get(),
            'notifications' => DB::table('site_topbar_notifications')->orderBy('sort_order')->orderByDesc('created_at')->limit(5)->get(),
        ]);
    }
}
