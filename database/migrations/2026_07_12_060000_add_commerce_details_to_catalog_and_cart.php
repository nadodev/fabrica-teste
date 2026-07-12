<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('category', 80)->default('Uniformes')->after('description');
            $table->json('gallery_images')->nullable()->after('image_url');
            $table->json('variations')->nullable()->after('gallery_images');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->string('cart_item_key', 160)->nullable()->after('product_id');
            $table->string('variation_key', 80)->nullable()->after('cart_item_key');
            $table->string('variation_label', 255)->nullable()->after('variation_key');
        });

        DB::table('cart_items')->orderBy('id')->get(['id', 'product_id'])->each(function (object $item): void {
            DB::table('cart_items')
                ->where('id', $item->id)
                ->update(['cart_item_key' => (string) $item->product_id]);
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'product_id']);
            $table->unique(['cart_id', 'cart_item_key']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'cart_item_key']);
            $table->unique(['cart_id', 'product_id']);
            $table->dropColumn(['cart_item_key', 'variation_key', 'variation_label']);
        });

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn(['category', 'gallery_images', 'variations']);
        });
    }
};
