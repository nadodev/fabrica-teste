<?php

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('shares public company appearance payment and policy settings with the storefront', function () {
    DB::table('site_settings')->where('id', 1)->update([
        'store_name' => 'Uniformes Acme',
        'legal_name' => 'Uniformes Acme Ltda',
        'document_number' => '12.345.678/0001-90',
        'contact_email' => 'contato@acme.test',
        'contact_phone' => '(11) 3333-4444',
        'whatsapp' => '(11) 99999-8888',
        'company_address' => 'Rua das Empresas, 10',
        'business_hours' => 'Segunda a sexta, 8h as 18h',
        'primary_color' => '#112233',
        'secondary_color' => '#ffee00',
        'payment_settings' => json_encode(['pixEnabled' => true, 'cardEnabled' => false, 'boletoEnabled' => false]),
        'policy_settings' => json_encode(['privacyUrl' => '/privacidade-acme', 'termsUrl' => '/termos-acme', 'cookieNotice' => true]),
    ]);

    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('siteSettings.storeName', 'Uniformes Acme')
        ->where('siteSettings.legalName', 'Uniformes Acme Ltda')
        ->where('siteSettings.contactEmail', 'contato@acme.test')
        ->where('siteSettings.companyAddress', 'Rua das Empresas, 10')
        ->where('siteSettings.primaryColor', '#112233')
        ->where('siteSettings.payments.pixEnabled', true)
        ->where('siteSettings.payments.cardEnabled', false)
        ->where('commerceSettings.policies.privacyUrl', '/privacidade-acme'));
});

it('uses the configured number of products per catalog page', function () {
    DB::table('site_settings')->where('id', 1)->update([
        'appearance_settings' => json_encode(['productsPerPage' => 4]),
    ]);

    foreach (range(1, 6) as $index) {
        ProductRecord::query()->create([
            'id' => (string) Str::uuid(),
            'sku' => "TEST-{$index}",
            'name' => "Produto {$index}",
            'description' => 'Produto para testar paginacao',
            'price_amount' => 1000 + $index,
            'price_currency' => 'BRL',
            'status' => 'active',
        ]);
    }

    $this->get(route('produtos'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('products', 4)
        ->where('pagination.currentPage', 1)
        ->where('pagination.lastPage', 2)
        ->where('pagination.total', 6));
});

it('serves configured robots content and a dynamic sitemap', function () {
    DB::table('site_settings')->where('id', 1)->update([
        'seo_settings' => json_encode(['robotsContent' => "User-agent: *\nDisallow: /admin", 'sitemapEnabled' => true]),
    ]);

    $this->get(route('robots'))->assertOk()->assertSeeText('Disallow: /admin');
    $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
});

it('persists only settings that have a real consumer', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->post(route('admin.settings.update'), [
        'storeName' => 'Loja Configurada',
        'primaryColor' => '#123456',
        'secondaryColor' => '#fedcba',
        'appearance' => ['productsPerPage' => 16, 'featuredProducts' => 'NAO-DEVE-SER-SALVO'],
        'products' => ['stockControl' => true, 'allowOutOfStock' => false, 'minQuantity' => 2, 'maxQuantity' => 40],
        'payments' => ['pixEnabled' => true, 'cardEnabled' => false, 'boletoEnabled' => true, 'gatewayCredentials' => 'NAO-DEVE-SER-SALVO'],
        'customers' => ['registrationRequired' => false, 'guestCheckout' => true, 'validateDocument' => true, 'privacyRequired' => true],
        'promotions' => ['couponsEnabled' => false, 'minimumOrderValue' => 5000],
        'emails' => ['senderName' => 'Loja Configurada', 'senderEmail' => 'loja@example.test', 'notifyNewOrder' => true, 'notifyQuote' => false, 'adminRecipients' => 'admin@example.test'],
        'policies' => ['termsUrl' => '/termos', 'privacyUrl' => '/privacidade', 'cookieNotice' => true, 'lgpdConsent' => true],
        'seo' => ['title' => 'Loja Configurada', 'sitemapEnabled' => true, 'socialIntegration' => true],
        'system' => ['productImportExport' => false, 'apiKeys' => 'NAO-DEVE-SER-SALVO'],
    ], ['Idempotency-Key' => 'settings-functional-test'])->assertRedirect();

    $settings = DB::table('site_settings')->where('id', 1)->firstOrFail();
    expect(json_decode((string) $settings->appearance_settings, true))->toBe(['productsPerPage' => 16])
        ->and(json_decode((string) $settings->payment_settings, true))->not->toHaveKey('gatewayCredentials')
        ->and(json_decode((string) $settings->system_settings, true))->toBe(['productImportExport' => false]);
});
