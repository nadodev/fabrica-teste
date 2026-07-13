<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class AdminSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/settings', [
            'settings' => $this->settings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'storeName' => ['required', 'string', 'max:120'],
            'documentNumber' => ['nullable', 'string', 'max:40'],
            'legalName' => ['nullable', 'string', 'max:160'],
            'contactEmail' => ['nullable', 'email', 'max:160'],
            'contactPhone' => ['nullable', 'string', 'max:60'],
            'whatsapp' => ['nullable', 'string', 'max:60'],
            'companyAddress' => ['nullable', 'string', 'max:1000'],
            'businessHours' => ['nullable', 'string', 'max:160'],
            'primaryColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondaryColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'socialLinks' => ['nullable', 'array', 'max:8'],
            'socialLinks.*.label' => ['nullable', 'string', 'max:80'],
            'socialLinks.*.url' => ['nullable', 'url:http,https', 'max:2048'],
            'appearance' => ['nullable', 'array'],
            'appearance.productsPerPage' => ['nullable', 'integer', 'min:4', 'max:100'],
            'products' => ['nullable', 'array'],
            'products.stockControl' => ['nullable', 'boolean'],
            'products.allowOutOfStock' => ['nullable', 'boolean'],
            'products.minQuantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'products.maxQuantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'payments' => ['nullable', 'array'],
            'payments.pixEnabled' => ['nullable', 'boolean'],
            'payments.cardEnabled' => ['nullable', 'boolean'],
            'payments.boletoEnabled' => ['nullable', 'boolean'],
            'customers' => ['nullable', 'array'],
            'customers.registrationRequired' => ['nullable', 'boolean'],
            'customers.guestCheckout' => ['nullable', 'boolean'],
            'customers.validateDocument' => ['nullable', 'boolean'],
            'customers.privacyRequired' => ['nullable', 'boolean'],
            'promotions' => ['nullable', 'array'],
            'promotions.couponsEnabled' => ['nullable', 'boolean'],
            'promotions.minimumOrderValue' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'emails' => ['nullable', 'array'],
            'emails.senderName' => ['nullable', 'string', 'max:120'],
            'emails.senderEmail' => ['nullable', 'email', 'max:160'],
            'emails.notifyNewOrder' => ['nullable', 'boolean'],
            'emails.notifyQuote' => ['nullable', 'boolean'],
            'emails.adminRecipients' => ['nullable', 'string', 'max:1000'],
            'policies' => ['nullable', 'array'],
            'policies.termsUrl' => ['nullable', 'string', 'max:2048'],
            'policies.privacyUrl' => ['nullable', 'string', 'max:2048'],
            'policies.exchangePolicy' => ['nullable', 'string', 'max:10000'],
            'policies.deliveryPolicy' => ['nullable', 'string', 'max:10000'],
            'policies.personalizationPolicy' => ['nullable', 'string', 'max:10000'],
            'policies.warrantyInfo' => ['nullable', 'string', 'max:10000'],
            'policies.cookieNotice' => ['nullable', 'boolean'],
            'policies.lgpdConsent' => ['nullable', 'boolean'],
            'seo' => ['nullable', 'array'],
            'seo.title' => ['nullable', 'string', 'max:160'],
            'seo.description' => ['nullable', 'string', 'max:500'],
            'seo.keywords' => ['nullable', 'string', 'max:500'],
            'seo.googleAnalytics' => ['nullable', 'string', 'max:40', 'regex:/^(G|UA)-[A-Z0-9-]+$/i'],
            'seo.googleTagManager' => ['nullable', 'string', 'max:40', 'regex:/^GTM-[A-Z0-9]+$/i'],
            'seo.metaPixel' => ['nullable', 'string', 'max:40', 'regex:/^[0-9]+$/'],
            'seo.sitemapEnabled' => ['nullable', 'boolean'],
            'seo.robotsContent' => ['nullable', 'string', 'max:5000'],
            'seo.socialIntegration' => ['nullable', 'boolean'],
            'system' => ['nullable', 'array'],
            'system.productImportExport' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'headerLogo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'footerLogo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'favicon' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg,ico', 'max:2048'],
            'shareImage' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $settings = $this->settings();
        $current = (array) $settings;
        $logoUrl = $current['logo_url'] ?? null;
        $headerLogoUrl = $current['header_logo_url'] ?? null;
        $footerLogoUrl = $current['footer_logo_url'] ?? null;
        $faviconUrl = $current['favicon_url'] ?? null;
        $shareImageUrl = $current['share_image_url'] ?? null;

        if ($request->hasFile('logo')) {
            $logoUrl = $this->storeLogo($request->file('logo'));
            $this->deleteManagedLogo($current['logo_url'] ?? null);
        }
        if ($request->hasFile('headerLogo')) {
            $headerLogoUrl = $this->storeLogo($request->file('headerLogo'));
            $this->deleteManagedLogo($current['header_logo_url'] ?? null);
        }
        if ($request->hasFile('footerLogo')) {
            $footerLogoUrl = $this->storeLogo($request->file('footerLogo'));
            $this->deleteManagedLogo($current['footer_logo_url'] ?? null);
        }
        if ($request->hasFile('favicon')) {
            $faviconUrl = $this->storeLogo($request->file('favicon'));
            $this->deleteManagedLogo($current['favicon_url'] ?? null);
        }
        if ($request->hasFile('shareImage')) {
            $shareImageUrl = $this->storeLogo($request->file('shareImage'));
            $this->deleteManagedLogo($current['share_image_url'] ?? null);
        }

        DB::table('site_settings')->updateOrInsert(
            ['id' => 1],
            [
                'store_name' => $data['storeName'],
                'document_number' => $data['documentNumber'] ?? null,
                'legal_name' => $data['legalName'] ?? null,
                'contact_email' => $data['contactEmail'] ?? null,
                'contact_phone' => $data['contactPhone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'company_address' => $data['companyAddress'] ?? null,
                'business_hours' => $data['businessHours'] ?? null,
                'primary_color' => $data['primaryColor'],
                'secondary_color' => $data['secondaryColor'],
                'header_logo_url' => $headerLogoUrl,
                'footer_logo_url' => $footerLogoUrl,
                'favicon_url' => $faviconUrl,
                'share_image_url' => $shareImageUrl,
                'social_links' => json_encode($this->socialLinks((array) ($data['socialLinks'] ?? [])), JSON_THROW_ON_ERROR),
                'appearance_settings' => $this->json($this->only($data['appearance'] ?? [], ['productsPerPage'])),
                'product_settings' => $this->json($this->only($data['products'] ?? [], ['stockControl', 'allowOutOfStock', 'minQuantity', 'maxQuantity'])),
                'payment_settings' => $this->json($this->only($data['payments'] ?? [], ['pixEnabled', 'cardEnabled', 'boletoEnabled'])),
                'customer_settings' => $this->json($this->only($data['customers'] ?? [], ['registrationRequired', 'guestCheckout', 'validateDocument', 'privacyRequired'])),
                'promotion_settings' => $this->json($this->only($data['promotions'] ?? [], ['couponsEnabled', 'minimumOrderValue'])),
                'email_settings' => $this->json($this->only($data['emails'] ?? [], ['senderName', 'senderEmail', 'notifyNewOrder', 'notifyQuote', 'adminRecipients'])),
                'policy_settings' => $this->json($this->only($data['policies'] ?? [], ['termsUrl', 'privacyUrl', 'exchangePolicy', 'deliveryPolicy', 'personalizationPolicy', 'warrantyInfo', 'cookieNotice', 'lgpdConsent'])),
                'seo_settings' => $this->json($this->only($data['seo'] ?? [], ['title', 'description', 'keywords', 'googleAnalytics', 'googleTagManager', 'metaPixel', 'sitemapEnabled', 'robotsContent', 'socialIntegration'])),
                'system_settings' => $this->json($this->only($data['system'] ?? [], ['productImportExport'])),
                'logo_url' => $logoUrl,
                'updated_at' => now(),
                'created_at' => $current['created_at'] ?? now(),
            ],
        );

        return back()->with('success', 'Configuracoes atualizadas com sucesso.');
    }

    private function settings(): object
    {
        $settings = DB::table('site_settings')->where('id', 1)->first();

        if ($settings !== null) {
            $settings->social_links = json_decode((string) ($settings->social_links ?? '[]'), true) ?: [];
            $settings->appearance_settings = $this->decode($settings->appearance_settings ?? null);
            $settings->product_settings = $this->decode($settings->product_settings ?? null);
            $settings->payment_settings = $this->decode($settings->payment_settings ?? null);
            $settings->customer_settings = $this->decode($settings->customer_settings ?? null);
            $settings->promotion_settings = $this->decode($settings->promotion_settings ?? null);
            $settings->email_settings = $this->decode($settings->email_settings ?? null);
            $settings->policy_settings = $this->decode($settings->policy_settings ?? null);
            $settings->seo_settings = $this->decode($settings->seo_settings ?? null);
            $settings->system_settings = $this->decode($settings->system_settings ?? null);

            return $settings;
        }

        return (object) [
            'id' => 1,
            'store_name' => 'Fabrica de Fardamentos',
            'document_number' => null,
            'legal_name' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'whatsapp' => null,
            'company_address' => null,
            'business_hours' => null,
            'logo_url' => '/logo.png',
            'header_logo_url' => null,
            'footer_logo_url' => null,
            'favicon_url' => null,
            'share_image_url' => null,
            'primary_color' => '#123a6b',
            'secondary_color' => '#f5c542',
            'social_links' => [],
            'appearance_settings' => [],
            'product_settings' => [],
            'payment_settings' => [],
            'customer_settings' => [],
            'promotion_settings' => [],
            'email_settings' => [],
            'policy_settings' => [],
            'seo_settings' => [],
            'system_settings' => [],
            'created_at' => now(),
        ];
    }

    private function json(mixed $value): string
    {
        return json_encode(is_array($value) ? $value : [], JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function only(array $value, array $keys): array
    {
        return array_intersect_key($value, array_flip($keys));
    }

    /** @return array<string, mixed> */
    private function decode(mixed $value): array
    {
        $decoded = json_decode((string) ($value ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $links
     * @return list<array{label: string, url: string}>
     */
    private function socialLinks(array $links): array
    {
        $clean = [];

        foreach ($links as $link) {
            $label = trim((string) ($link['label'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));

            if ($label !== '' && $url !== '') {
                $clean[] = ['label' => mb_substr($label, 0, 80), 'url' => $url];
            }
        }

        return $clean;
    }

    private function storeLogo(?UploadedFile $logo): ?string
    {
        if ($logo === null) {
            return null;
        }

        $path = $logo->store('site', 'public');

        if ($path === false) {
            throw new RuntimeException('Logo could not be stored.');
        }

        return '/storage/'.$path;
    }

    private function deleteManagedLogo(mixed $url): void
    {
        if (is_string($url) && str_starts_with($url, '/storage/site/')) {
            Storage::disk('public')->delete(substr($url, strlen('/storage/')));
        }
    }
}
