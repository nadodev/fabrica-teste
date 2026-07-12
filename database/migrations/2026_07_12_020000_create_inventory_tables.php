<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table): void {
            $table->uuid('product_id')->primary();
            $table->unsignedBigInteger('on_hand')->default(0);
            $table->unsignedBigInteger('reserved')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('catalog_products')->cascadeOnDelete();
        });

        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->index();
            $table->unsignedInteger('quantity');
            $table->string('status', 16)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('product_id')->index();
            $table->string('type', 32);
            $table->bigInteger('quantity');
            $table->unsignedBigInteger('balance_after');
            $table->string('reference', 128)->unique();
            $table->timestamp('created_at');
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_stock');
    }
};
