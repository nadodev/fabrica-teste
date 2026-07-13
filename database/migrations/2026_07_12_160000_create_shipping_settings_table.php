<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('environment', 20)->default('sandbox');
            $table->text('token')->nullable();
            $table->string('origin_zip', 20)->nullable();
            $table->timestamps();
        });

        DB::table('shipping_settings')->insert([
            'id' => 1,
            'is_enabled' => false,
            'environment' => 'sandbox',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};
