<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nasabah terhubung ke akun Google (users.role = nasabah) melalui user_id.
 * PIN dihapus sepenuhnya: tidak ada login dengan PIN.
 * Saldo disimpan di kolom saldo + histori di tabel mutasi_saldo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->unique()->constrained('users')->nullOnDelete();
            $table->string('nomor_nasabah', 30)->nullable()->unique()->after('bank_sampah_id');
            $table->decimal('saldo', 15, 2)->default(0)->after('alamat_rt_rw');
            $table->string('status', 20)->default('aktif')->after('status_verifikasi');
        });

        Schema::table('nasabah', function (Blueprint $table): void {
            $table->dropColumn('pin');
        });

        DB::table('nasabah')->orderBy('id')->eachById(function (object $nasabah): void {
            DB::table('nasabah')->where('id', $nasabah->id)->update([
                'nomor_nasabah' => 'NSB-'.str_pad((string) $nasabah->id, 6, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->string('pin')->nullable();
        });

        Schema::table('nasabah', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropUnique(['nomor_nasabah']);
            $table->dropColumn(['nomor_nasabah', 'saldo', 'status']);
        });
    }
};
