<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Histori setiap perubahan saldo nasabah (ledger).
 * Saldo tidak pernah diubah tanpa baris mutasi yang mencatat sumbernya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_saldo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nasabah_id')->constrained('nasabah')->restrictOnDelete();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->restrictOnDelete();
            $table->string('tipe', 10); // kredit | debit
            $table->decimal('jumlah', 15, 2);
            $table->decimal('saldo_sebelum', 15, 2);
            $table->decimal('saldo_sesudah', 15, 2);
            $table->nullableMorphs('sumber');
            $table->string('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nasabah_id', 'created_at']);
        });

        // Saldo awal dari data lama: total setoran anorganik - penarikan selesai.
        $now = now();
        DB::table('nasabah')->orderBy('id')->eachById(function (object $nasabah) use ($now): void {
            $masuk = (float) DB::table('transaksi_anorganik')->where('nasabah_id', $nasabah->id)->sum('nilai_rupiah');
            $keluar = (float) DB::table('penarikan_saldo')->where('nasabah_id', $nasabah->id)->where('status', 'selesai')->sum('jumlah');
            $saldo = $masuk - $keluar;

            DB::table('nasabah')->where('id', $nasabah->id)->update(['saldo' => $saldo]);

            if ($saldo != 0.0) {
                DB::table('mutasi_saldo')->insert([
                    'nasabah_id' => $nasabah->id,
                    'bank_sampah_id' => $nasabah->bank_sampah_id,
                    'tipe' => $saldo > 0 ? 'kredit' : 'debit',
                    'jumlah' => abs($saldo),
                    'saldo_sebelum' => 0,
                    'saldo_sesudah' => $saldo,
                    'keterangan' => 'Saldo awal (migrasi data lama)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_saldo');
    }
};
