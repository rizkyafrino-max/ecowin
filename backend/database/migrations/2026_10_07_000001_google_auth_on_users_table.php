<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Semua role (admin, petugas, nasabah) login dengan Google.
 * Kolom password dipertahankan nullable hanya untuk kompatibilitas data lama
 * dan TIDAK lagi dipakai sebagai metode login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('petugas')->change();
            $table->string('password')->nullable()->change();
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('google_id');
            $table->string('avatar')->nullable()->after('email_verified_at');
            $table->string('status', 20)->default('aktif')->after('role')->index();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['google_id', 'email_verified_at', 'avatar', 'status', 'remember_token', 'last_login_at']);
        });
    }
};
