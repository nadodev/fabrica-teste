<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_super_admin')->default(false)->index()->after('is_admin');
        });

        DB::table('users')->where('is_admin', true)->update(['is_super_admin' => true]);

        Schema::create('admin_user_permissions', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 80);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'permission']);
            $table->index(['permission', 'user_id']);
        });

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 160)->index();
            $table->string('subject_type', 100)->nullable();
            $table->string('subject_id', 160)->nullable();
            $table->string('outcome', 30);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['created_at', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('admin_user_permissions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn('is_super_admin');
        });
    }
};
