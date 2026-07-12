<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'siteSettings' => $this->siteSettings(),
            'topbarNotification' => $this->topbarNotification(),
            'cartSummary' => $this->cartSummary($request),
            'auth' => [
                'user' => $request->user(),
            ],
        ];
    }

    /** @return array{storeName: string, logoUrl: string, primaryColor: string, secondaryColor: string} */
    private function siteSettings(): array
    {
        $settings = DB::table('site_settings')->where('id', 1)->first();

        return [
            'storeName' => (string) ($settings->store_name ?? 'Fabrica de Fardamentos'),
            'logoUrl' => (string) ($settings->logo_url ?? '/logo.png'),
            'primaryColor' => (string) ($settings->primary_color ?? '#123a6b'),
            'secondaryColor' => (string) ($settings->secondary_color ?? '#f5c542'),
        ];
    }

    /** @return array{message: string, linkLabel: string|null, linkUrl: string|null}|null */
    private function topbarNotification(): ?array
    {
        $now = now();
        $notification = DB::table('site_topbar_notifications')
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->first();

        if ($notification === null) {
            return null;
        }

        return [
            'message' => (string) $notification->message,
            'linkLabel' => $notification->link_label === null ? null : (string) $notification->link_label,
            'linkUrl' => $notification->link_url === null ? null : (string) $notification->link_url,
        ];
    }

    /** @return array{itemsCount: int} */
    private function cartSummary(Request $request): array
    {
        $token = $request->session()->get('cart_token');

        if (! is_string($token) || $token === '') {
            return ['itemsCount' => 0];
        }

        $cart = DB::table('cart_carts')
            ->where('token_hash', hash('sha256', $token))
            ->where('status', 'active')
            ->first(['id']);

        if ($cart === null) {
            return ['itemsCount' => 0];
        }

        return [
            'itemsCount' => (int) DB::table('cart_items')
                ->where('cart_id', $cart->id)
                ->sum('quantity'),
        ];
    }
}
