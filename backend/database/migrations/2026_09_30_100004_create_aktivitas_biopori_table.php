<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_biopori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nasabah_id')
                ->constrained('nasabah')
                ->onDelete('restrict');
            $table->foreignId('bank_sampah_id')
                ->constrained('bank_sampah')
                ->onDelete('restrict');
            $table->timestamp('tanggal_pemasukan');
            $table->decimal('berat_kg', 8, 2)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('foto_bukti_path');  // wajib ada, required di validation
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('diperiksa_oleh')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->timestamp('waktu_diperiksa')->nullable();
            $table->text('catatan_petugas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_biopori');
    }
};
