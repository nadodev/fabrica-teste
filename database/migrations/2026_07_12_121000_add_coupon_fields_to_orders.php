<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->string('coupon_code', 40)->nullable()->after('currency');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('coupon_code');
            $table->unsignedBigInteger('subtotal_amount')->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->dropColumn(['coupon_code', 'discount_amount', 'subtotal_amount']);
        });
    }
};
