<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log lengkap: user, aktivitas, model, model_id, data sebelum/sesudah,
 * IP, user agent, timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_log', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('audit_log', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('model_type')->nullable()->after('aksi');
            $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            $table->json('data_sebelum')->nullable()->after('model_id');
            $table->json('data_sesudah')->nullable()->after('data_sebelum');
            $table->string('ip_address', 45)->nullable()->after('detail');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table): void {
            $table->dropIndex(['model_type', 'model_id']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['model_type', 'model_id', 'data_sebelum', 'data_sesudah', 'ip_address', 'user_agent']);
        });
    }
};
