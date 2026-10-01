<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_anorganik', function (Blueprint $table) {
            $table->foreignId('bank_sampah_id')
                ->after('nasabah_id')
                ->constrained('bank_sampah')
                ->onDelete('restrict');
        });

        Schema::table('transaksi_organik', function (Blueprint $table) {
            $table->foreignId('bank_sampah_id')
                ->after('nasabah_id')
                ->constrained('bank_sampah')
                ->onDelete('restrict');
        });

        Schema::table('penarikan_saldo', function (Blueprint $table) {
            $table->foreignId('bank_sampah_id')
                ->after('nasabah_id')
                ->constrained('bank_sampah')
                ->onDelete('restrict');
        });

        Schema::table('laporan_kendala', function (Blueprint $table) {
            // Kolom sudah ada di spesifikasi tapi belum ada di migration awal
            if (! Schema::hasColumn('laporan_kendala', 'bank_sampah_id')) {
                $table->foreignId('bank_sampah_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('bank_sampah')
                    ->onDelete('restrict');
            }
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->foreignId('bank_sampah_id')
                ->nullable()
                ->after('user_id')
                ->constrained('bank_sampah')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_anorganik', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn('bank_sampah_id');
        });

        Schema::table('transaksi_organik', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn('bank_sampah_id');
        });

        Schema::table('penarikan_saldo', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn('bank_sampah_id');
        });

        Schema::table('laporan_kendala', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn('bank_sampah_id');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn('bank_sampah_id');
        });
    }
};
