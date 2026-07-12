<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('store_name')->default('Fabrica de Fardamentos');
            $table->string('logo_url')->nullable();
            $table->string('primary_color', 20)->default('#123a6b');
            $table->string('secondary_color', 20)->default('#f5c542');
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'store_name' => 'Fabrica de Fardamentos',
            'logo_url' => '/logo.png',
            'primary_color' => '#123a6b',
            'secondary_color' => '#f5c542',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
