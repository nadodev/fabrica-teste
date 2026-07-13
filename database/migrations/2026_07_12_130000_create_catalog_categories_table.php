<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('description')->default('');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('catalog_products')
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->each(function (string $category, int $index) use ($now): void {
                DB::table('catalog_categories')->insertOrIgnore([
                    'id' => (string) Str::uuid(),
                    'name' => $category,
                    'description' => '',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_categories');
    }
};
