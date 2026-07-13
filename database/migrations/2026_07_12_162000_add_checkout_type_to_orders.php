<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->string('checkout_type', 24)->default('payment')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ordering_orders', function (Blueprint $table): void {
            $table->dropColumn('checkout_type');
        });
    }
};
