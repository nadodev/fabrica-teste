<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('method', 40);
            $table->string('status', 20)->index();
            $table->string('provider', 32)->default('fake');
            $table->string('provider_payment_id')->nullable()->index();
            $table->uuid('idempotency_key')->unique();
            $table->boolean('stock_reserved')->default(true);
            $table->string('failure_code', 64)->nullable();
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('ordering_orders')->restrictOnDelete();
        });

        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->index();
            $table->unsignedInteger('attempt_number');
            $table->string('operation', 20)->default('charge');
            $table->string('status', 20);
            $table->string('provider_transaction_id')->nullable();
            $table->string('response_code', 64)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unique(['payment_id', 'operation', 'attempt_number']);
            $table->foreign('payment_id')->references('id')->on('payment_payments')->cascadeOnDelete();
        });

        Schema::create('payment_status_history', function (Blueprint $table): void {
            $table->id();
            $table->uuid('payment_id')->index();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('source', 40);
            $table->timestamp('created_at');
            $table->foreign('payment_id')->references('id')->on('payment_payments')->cascadeOnDelete();
        });

        Schema::create('payment_fake_transactions', function (Blueprint $table): void {
            $table->uuid('idempotency_key')->primary();
            $table->string('provider_transaction_id')->unique();
            $table->uuid('order_id')->index();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('outcome', 20);
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('payment_fake_transactions');
        Schema::dropIfExists('payment_status_history');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payment_payments');
    }
};
