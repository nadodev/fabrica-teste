<?php

namespace App\Http\Middleware;

use App\Modules\Administration\Application\Port\AdminPermissionChecker;
use App\Support\StoreSettings;
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
            'catalogCategories' => $this->catalogCategories(),
            'cartSummary' => $this->cartSummary($request),
            'commerceSettings' => [
                'appearance' => app(StoreSettings::class)->appearance(),
                'products' => app(StoreSettings::class)->products(),
                'payments' => app(StoreSettings::class)->payments(),
                'customers' => app(StoreSettings::class)->customers(),
                'promotions' => app(StoreSettings::class)->promotions(),
                'policies' => app(StoreSettings::class)->policies(),
                'system' => app(StoreSettings::class)->system(),
            ],
            'auth' => [
                'user' => $request->user() === null ? null : [
                    'id' => (int) $request->user()->getAuthIdentifier(),
                    'name' => (string) $request->user()->name,
                    'email' => (string) $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at?->toIso8601String(),
                    'is_admin' => (bool) $request->user()->is_admin,
                    'is_super_admin' => (bool) $request->user()->is_super_admin,
                    'permissions' => $request->user()->is_admin
                        ? app(AdminPermissionChecker::class)->permissionValues((int) $request->user()->getAuthIdentifier())
                        : [],
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function siteSettings(): array
    {
        $settings = DB::table('site_settings')->where('id', 1)->first();
        $socialLinks = json_decode((string) ($settings->social_links ?? '[]'), true);
        $storeSettings = app(StoreSettings::class);

        return [
            'storeName' => (string) ($settings->store_name ?? 'Fabrica de Fardamentos'),
            'legalName' => (string) ($settings->legal_name ?? ''),
            'documentNumber' => (string) ($settings->document_number ?? ''),
            'logoUrl' => (string) ($settings->header_logo_url ?? $settings->logo_url ?? '/logo.png'),
            'mainLogoUrl' => (string) ($settings->logo_url ?? '/logo.png'),
            'footerLogoUrl' => (string) ($settings->footer_logo_url ?? $settings->logo_url ?? '/logo.png'),
            'faviconUrl' => $settings->favicon_url ?? null,
            'primaryColor' => (string) ($settings->primary_color ?? '#123a6b'),
            'secondaryColor' => (string) ($settings->secondary_color ?? '#f5c542'),
            'contactEmail' => (string) ($settings->contact_email ?? ''),
            'contactPhone' => (string) ($settings->contact_phone ?? ''),
            'whatsapp' => (string) ($settings->whatsapp ?? ''),
            'businessHours' => (string) ($settings->business_hours ?? ''),
            'companyAddress' => (string) ($settings->company_address ?? ''),
            'shareImageUrl' => $settings->share_image_url ?? null,
            'appearance' => $storeSettings->appearance(),
            'payments' => $storeSettings->payments(),
            'policies' => $storeSettings->policies(),
            'seo' => $storeSettings->seo(),
            'socialLinks' => is_array($socialLinks) ? array_values($socialLinks) : [],
        ];
    }

    /** @return list<array{name: string, slug: string|null, imageUrl: string|null}> */
    private function catalogCategories(): array
    {
        $categories = DB::table('catalog_categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug', 'image_url'])
            ->map(fn (object $category): array => [
                'name' => (string) $category->name,
                'slug' => $category->slug === null ? null : (string) $category->slug,
                'imageUrl' => $category->image_url === null ? null : (string) $category->image_url,
            ])
            ->all();

        return array_values($categories);
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
