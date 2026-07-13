<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_fake_refunds')) {
            return;
        }

        Schema::create('payment_fake_refunds', function (Blueprint $table): void {
            $table->uuid('idempotency_key')->primary();
            $table->string('provider_transaction_id')->index();
            $table->unsignedBigInteger('amount');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_fake_refunds');
    }
};
