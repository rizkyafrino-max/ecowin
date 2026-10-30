<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nasabah dapat mengajukan titik Biopori baru. Titik yang diajukan berstatus "pending" sampai
     * petugas Bank Sampah menyetujuinya; titik yang sudah ada otomatis "approved".
     */
    public function up(): void
    {
        Schema::table('titik_biopori', function (Blueprint $table): void {
            $table->string('verifikasi', 20)->default('approved')->after('status')->index();
            $table->foreignId('diajukan_oleh')->nullable()->after('verifikasi')->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable()->after('diajukan_oleh');
            $table->foreignId('diverifikasi_oleh')->nullable()->after('catatan_verifikasi')->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable()->after('diverifikasi_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('titik_biopori', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('diajukan_oleh');
            $table->dropConstrainedForeignId('diverifikasi_oleh');
            $table->dropColumn(['verifikasi', 'catatan_verifikasi', 'diverifikasi_at']);
        });
    }
};
