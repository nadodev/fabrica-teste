<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->uuid('reservation_id')->nullable()->after('sku')->index();
            $table->bigInteger('reserved_delta')->default(0)->after('quantity');
            $table->unsignedBigInteger('reserved_after')->nullable()->after('balance_after');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropIndex(['reservation_id']);
            $table->dropColumn(['reservation_id', 'reserved_delta', 'reserved_after']);
        });
    }
};
