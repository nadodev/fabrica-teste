<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerce_coupons', function (Blueprint $table): void {
            $table->unsignedBigInteger('minimum_amount')->default(0)->after('discount_value');
            $table->unsignedInteger('usage_limit')->nullable()->after('minimum_amount');
            $table->unsignedInteger('used_count')->default(0)->after('usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('commerce_coupons', function (Blueprint $table): void {
            $table->dropColumn(['minimum_amount', 'usage_limit', 'used_count']);
        });
    }
};
