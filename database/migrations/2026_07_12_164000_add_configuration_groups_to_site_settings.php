<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('document_number', 40)->nullable()->after('store_name');
            $table->string('legal_name', 160)->nullable()->after('document_number');
            $table->string('contact_email', 160)->nullable()->after('legal_name');
            $table->string('contact_phone', 60)->nullable()->after('contact_email');
            $table->string('whatsapp', 60)->nullable()->after('contact_phone');
            $table->text('company_address')->nullable()->after('whatsapp');
            $table->string('business_hours', 160)->nullable()->after('company_address');
            $table->string('header_logo_url')->nullable()->after('logo_url');
            $table->string('footer_logo_url')->nullable()->after('header_logo_url');
            $table->string('favicon_url')->nullable()->after('footer_logo_url');
            $table->string('share_image_url')->nullable()->after('favicon_url');
            $table->json('appearance_settings')->nullable()->after('social_links');
            $table->json('product_settings')->nullable()->after('appearance_settings');
            $table->json('payment_settings')->nullable()->after('product_settings');
            $table->json('customer_settings')->nullable()->after('payment_settings');
            $table->json('promotion_settings')->nullable()->after('customer_settings');
            $table->json('email_settings')->nullable()->after('promotion_settings');
            $table->json('policy_settings')->nullable()->after('email_settings');
            $table->json('seo_settings')->nullable()->after('policy_settings');
            $table->json('system_settings')->nullable()->after('seo_settings');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'document_number',
                'legal_name',
                'contact_email',
                'contact_phone',
                'whatsapp',
                'company_address',
                'business_hours',
                'header_logo_url',
                'footer_logo_url',
                'favicon_url',
                'share_image_url',
                'appearance_settings',
                'product_settings',
                'payment_settings',
                'customer_settings',
                'promotion_settings',
                'email_settings',
                'policy_settings',
                'seo_settings',
                'system_settings',
            ]);
        });
    }
};
