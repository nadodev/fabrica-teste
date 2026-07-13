<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->string('delivery_method', 40)->default('shipping')->after('shipping_state');
            $table->string('shipping_service')->nullable()->after('delivery_method');
            $table->string('shipping_company')->nullable()->after('shipping_service');
            $table->unsignedBigInteger('shipping_amount')->default(0)->after('shipping_company');
            $table->unsignedInteger('shipping_delivery_time')->nullable()->after('shipping_amount');
            $table->string('payment_method', 40)->default('pix')->after('shipping_delivery_time');
            $table->string('payment_status', 40)->default('pending')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_method',
                'shipping_service',
                'shipping_company',
                'shipping_amount',
                'shipping_delivery_time',
                'payment_method',
                'payment_status',
            ]);
        });
    }
};
