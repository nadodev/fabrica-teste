<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('site_settings', 'secondary_color')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('secondary_color', 20)->default('#f5c542')->after('primary_color');
        });

        DB::table('site_settings')
            ->whereNull('secondary_color')
            ->orWhere('secondary_color', '')
            ->update(['secondary_color' => '#f5c542']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'secondary_color')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('secondary_color');
        });
    }
};
