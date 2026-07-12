<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_carts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('currency', 3)->default('BRL');
            $table->string('status', 16)->index();
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('cart_id');
            $table->uuid('product_id');
            $table->string('sku', 64);
            $table->string('name', 160);
            $table->unsignedBigInteger('unit_price_amount');
            $table->char('price_currency', 3);
            $table->unsignedInteger('quantity');
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'product_id']);
            $table->foreign('cart_id')->references('id')->on('cart_carts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('cart_carts');
    }
};
