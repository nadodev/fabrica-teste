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
        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable()->unique()->after('name');
            $table->string('image_url')->nullable()->after('description');
        });

        DB::table('catalog_categories')->orderBy('name')->get(['id', 'name'])->each(function (object $category): void {
            DB::table('catalog_categories')->where('id', $category->id)->update([
                'slug' => Str::slug((string) $category->name) ?: (string) $category->id,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->dropColumn(['slug', 'image_url']);
        });
    }
};
