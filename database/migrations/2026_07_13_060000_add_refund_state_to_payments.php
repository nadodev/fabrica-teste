<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('refunded_amount')->default(0)->after('failure_code');
        });
    }

    public function down(): void
    {
        Schema::table('payment_payments', function (Blueprint $table): void {
            $table->dropColumn('refunded_amount');
        });
    }
};
