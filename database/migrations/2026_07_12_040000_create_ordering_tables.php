<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordering_counters', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        Schema::create('ordering_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('number', 24)->unique();
            $table->uuid('cart_id')->unique();
            $table->string('status', 32)->index();
            $table->unsignedBigInteger('total_amount');
            $table->char('currency', 3);
            $table->timestamps();
            $table->foreign('cart_id')->references('id')->on('cart_carts')->restrictOnDelete();
        });

        Schema::create('ordering_order_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('order_id');
            $table->uuid('product_id');
            $table->string('sku', 64);
            $table->string('name', 160);
            $table->unsignedBigInteger('unit_price_amount');
            $table->char('price_currency', 3);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('subtotal_amount');
            $table->foreign('order_id')->references('id')->on('ordering_orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordering_order_items');
        Schema::dropIfExists('ordering_orders');
        Schema::dropIfExists('ordering_counters');
    }
};
