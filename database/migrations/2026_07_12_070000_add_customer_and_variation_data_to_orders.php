<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->string('customer_name')->nullable()->after('status');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone', 40)->nullable()->after('customer_email');
            $table->string('customer_document', 40)->nullable()->after('customer_phone');
            $table->string('shipping_zip', 20)->nullable()->after('customer_document');
            $table->string('shipping_address')->nullable()->after('shipping_zip');
            $table->string('shipping_number', 40)->nullable()->after('shipping_address');
            $table->string('shipping_city', 120)->nullable()->after('shipping_number');
            $table->string('shipping_state', 40)->nullable()->after('shipping_city');
            $table->text('notes')->nullable()->after('shipping_state');
        });

        Schema::table('ordering_order_items', function (Blueprint $table): void {
            $table->string('variation_key', 80)->nullable()->after('product_id');
            $table->string('variation_label', 255)->nullable()->after('variation_key');
        });
    }

    public function down(): void
    {
        Schema::table('ordering_order_items', function (Blueprint $table): void {
            $table->dropColumn(['variation_key', 'variation_label']);
        });

        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_name',
                'customer_email',
                'customer_phone',
                'customer_document',
                'shipping_zip',
                'shipping_address',
                'shipping_number',
                'shipping_city',
                'shipping_state',
                'notes',
            ]);
        });
    }
};
