<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table): void {
            $table->dropColumn('token');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table): void {
            $table->text('token')->nullable();
        });
    }
};
