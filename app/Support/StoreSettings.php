<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class StoreSettings
{
    public function storeName(): string
    {
        return (string) (DB::table('site_settings')->where('id', 1)->value('store_name') ?: config('app.name'));
    }

    /** @return array<string, mixed> */
    public function appearance(): array
    {
        return $this->json('appearance_settings') + [
            'productsPerPage' => 12,
        ];
    }

    /** @return array<string, mixed> */
    public function products(): array
    {
        return $this->json('product_settings') + [
            'stockControl' => true,
            'allowOutOfStock' => false,
            'lowStockWarning' => 5,
        ];
    }

    /** @return array<string, mixed> */
    public function system(): array
    {
        return $this->json('system_settings') + [
            'productImportExport' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function payments(): array
    {
        return $this->json('payment_settings') + [
            'pixEnabled' => true,
            'cardEnabled' => true,
            'boletoEnabled' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function customers(): array
    {
        return $this->json('customer_settings') + [
            'registrationRequired' => false,
            'guestCheckout' => true,
            'validateDocument' => false,
            'privacyRequired' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function promotions(): array
    {
        return $this->json('promotion_settings') + [
            'couponsEnabled' => true,
            'minimumOrderValue' => 0,
        ];
    }

    /** @return array<string, mixed> */
    public function emails(): array
    {
        return $this->json('email_settings') + [
            'senderName' => '',
            'senderEmail' => '',
            'notifyNewOrder' => true,
            'notifyQuote' => true,
            'adminRecipients' => '',
        ];
    }

    /** @return array<string, mixed> */
    public function policies(): array
    {
        return $this->json('policy_settings') + [
            'termsUrl' => '/termos',
            'privacyUrl' => '/privacidade',
            'exchangePolicy' => '',
            'deliveryPolicy' => '',
            'personalizationPolicy' => '',
            'warrantyInfo' => '',
            'cookieNotice' => true,
            'lgpdConsent' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function seo(): array
    {
        return $this->json('seo_settings') + [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'googleAnalytics' => '',
            'googleTagManager' => '',
            'metaPixel' => '',
            'sitemapEnabled' => true,
            'robotsContent' => '',
            'socialIntegration' => true,
        ];
    }

    public function couponsEnabled(): bool
    {
        return (bool) ($this->promotions()['couponsEnabled'] ?? true);
    }

    public function minimumOrderAmount(): int
    {
        return max(0, (int) ($this->promotions()['minimumOrderValue'] ?? 0));
    }

    /** @return list<string> */
    public function enabledPaymentMethods(): array
    {
        $payments = $this->payments();
        $methods = [];

        if ((bool) ($payments['pixEnabled'] ?? false)) {
            $methods[] = 'pix';
        }
        if ((bool) ($payments['cardEnabled'] ?? false)) {
            $methods[] = 'credit_card';
        }
        if ((bool) ($payments['boletoEnabled'] ?? false)) {
            $methods[] = 'boleto';
        }

        return $methods;
    }

    public function controlsStock(): bool
    {
        $settings = $this->products();

        return (bool) ($settings['stockControl'] ?? true) && ! (bool) ($settings['allowOutOfStock'] ?? false);
    }

    public function allowsOutOfStockSales(): bool
    {
        return (bool) ($this->products()['allowOutOfStock'] ?? false);
    }

    public function lowStockWarningsEnabled(): bool
    {
        $settings = $this->products();

        return $this->controlsStock() && ! (bool) ($settings['allowOutOfStock'] ?? false);
    }

    /** @return array<string, mixed> */
    private function json(string $column): array
    {
        $value = DB::table('site_settings')->where('id', 1)->value($column);
        $decoded = json_decode((string) ($value ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }
}
