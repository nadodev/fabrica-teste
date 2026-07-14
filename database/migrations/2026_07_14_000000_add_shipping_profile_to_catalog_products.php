<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->unsignedInteger('weight_grams')->default(300)->after('price_currency');
            $table->unsignedSmallInteger('width_centimeters')->default(20)->after('weight_grams');
            $table->unsignedSmallInteger('height_centimeters')->default(5)->after('width_centimeters');
            $table->unsignedSmallInteger('length_centimeters')->default(30)->after('height_centimeters');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn(['weight_grams', 'width_centimeters', 'height_centimeters', 'length_centimeters']);
        });
    }
};
