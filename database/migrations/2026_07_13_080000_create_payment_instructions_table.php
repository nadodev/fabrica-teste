<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_instructions', function (Blueprint $table): void {
            $table->uuid('payment_id')->primary();
            $table->text('payment_url')->nullable();
            $table->text('pix_payload')->nullable();
            $table->longText('pix_encoded_image')->nullable();
            $table->timestamp('pix_expiration_at')->nullable();
            $table->timestamps();
            $table->foreign('payment_id')->references('id')->on('payment_payments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_instructions');
    }
};
