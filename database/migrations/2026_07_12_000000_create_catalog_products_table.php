<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('sku', 64)->unique();
            $table->string('name', 160);
            $table->text('description')->default('');
            $table->unsignedBigInteger('price_amount');
            $table->char('price_currency', 3)->default('BRL');
            $table->string('status', 16)->index();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
