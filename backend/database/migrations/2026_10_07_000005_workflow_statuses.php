<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyeragamkan status alur kerja sesuai requirement:
 * - penarikan: pending | approved | rejected | completed
 * - biopori & koreksi: pending | approved | rejected
 * Serta kolom pencatatan siapa/kapan memutuskan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penarikan_saldo', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
            $table->text('catatan')->nullable()->after('status');
            $table->timestamp('diproses_at')->nullable()->after('diproses_oleh');
            $table->timestamp('diselesaikan_at')->nullable()->after('diproses_at');
        });
        DB::table('penarikan_saldo')->where('status', 'selesai')->update(['status' => 'completed']);

        Schema::table('aktivitas_biopori', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
            $table->foreignId('titik_biopori_id')->nullable()->after('bank_sampah_id')->constrained('titik_biopori')->nullOnDelete();
            $table->string('jenis_sampah', 100)->nullable()->after('tanggal_pemasukan');
            $table->json('data_awal')->nullable()->after('catatan_petugas');
        });
        DB::table('aktivitas_biopori')->where('status', 'menunggu')->update(['status' => 'pending']);
        DB::table('aktivitas_biopori')->where('status', 'disetujui')->update(['status' => 'approved']);
        DB::table('aktivitas_biopori')->where('status', 'ditolak')->update(['status' => 'rejected']);

        Schema::table('titik_biopori', function (Blueprint $table): void {
            $table->string('nama_lokasi')->nullable()->after('bank_sampah_id');
            $table->boolean('bioporiprint')->default(true)->after('jumlah_pipa');
            $table->timestamp('terakhir_diisi_at')->nullable()->after('status');
            $table->timestamp('estimasi_panen_at')->nullable()->after('terakhir_diisi_at');
            $table->string('status_panen', 20)->default('belum_panen')->after('estimasi_panen_at');
            $table->timestamp('dipanen_at')->nullable()->after('status_panen');
            $table->decimal('hasil_kompos_kg', 10, 2)->nullable()->after('dipanen_at');
        });

        Schema::table('koreksi_transaksi', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
            $table->foreignId('bank_sampah_id')->nullable()->after('tipe_transaksi')->constrained('bank_sampah')->nullOnDelete();
            $table->json('data_koreksi')->nullable()->after('alasan');
            $table->json('data_sebelum')->nullable()->after('data_koreksi');
            $table->text('catatan_admin')->nullable()->after('data_sebelum');
            $table->timestamp('diputuskan_at')->nullable()->after('catatan_admin');
        });
        DB::table('koreksi_transaksi')->where('status', 'menunggu')->update(['status' => 'pending']);
        DB::table('koreksi_transaksi')->where('status', 'disetujui')->update(['status' => 'approved']);
        DB::table('koreksi_transaksi')->where('status', 'ditolak')->update(['status' => 'rejected']);

        Schema::table('transaksi_organik', function (Blueprint $table): void {
            $table->date('tanggal')->nullable()->after('bank_sampah_id');
            $table->string('lokasi')->nullable()->after('jenis_organik');
            $table->string('metode_pengolahan', 30)->default('komposter')->after('lokasi');
            $table->string('status_pengolahan', 20)->default('diproses')->after('estimasi_kompos_kg');
        });

        Schema::table('transaksi_anorganik', function (Blueprint $table): void {
            $table->decimal('harga_per_kg', 15, 2)->nullable()->after('berat_kg');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_anorganik', function (Blueprint $table): void {
            $table->dropColumn('harga_per_kg');
        });
        Schema::table('transaksi_organik', function (Blueprint $table): void {
            $table->dropColumn(['tanggal', 'lokasi', 'metode_pengolahan', 'status_pengolahan']);
        });
        Schema::table('koreksi_transaksi', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bank_sampah_id');
            $table->dropColumn(['data_koreksi', 'data_sebelum', 'catatan_admin', 'diputuskan_at']);
        });
        Schema::table('titik_biopori', function (Blueprint $table): void {
            $table->dropColumn(['nama_lokasi', 'bioporiprint', 'terakhir_diisi_at', 'estimasi_panen_at', 'status_panen', 'dipanen_at', 'hasil_kompos_kg']);
        });
        Schema::table('aktivitas_biopori', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('titik_biopori_id');
            $table->dropColumn(['jenis_sampah', 'data_awal']);
        });
        Schema::table('penarikan_saldo', function (Blueprint $table): void {
            $table->dropColumn(['catatan', 'diproses_at', 'diselesaikan_at']);
        });
    }
};
