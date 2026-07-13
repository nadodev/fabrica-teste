<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('document', 40)->nullable()->after('phone');
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('label', 80);
            $table->string('postal_code', 8);
            $table->string('street');
            $table->string('number', 40);
            $table->string('city', 120);
            $table->string('state', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'document']);
        });
    }
};
