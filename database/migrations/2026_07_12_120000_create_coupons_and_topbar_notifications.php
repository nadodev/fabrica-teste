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
        Schema::create('commerce_coupons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('description')->default('');
            $table->string('discount_type', 20);
            $table->unsignedInteger('discount_value');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_topbar_notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('message', 180);
            $table->string('link_label', 80)->nullable();
            $table->string('link_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('site_topbar_notifications')->insert([
            'id' => (string) Str::uuid(),
            'message' => 'Ofertas e condicoes especiais para uniformes profissionais.',
            'link_label' => 'Ver produtos',
            'link_url' => '/produtos',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_topbar_notifications');
        Schema::dropIfExists('commerce_coupons');
    }
};
