<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengumuman dari Admin/Petugas ke nasabah (mis. jadwal pengumpulan sampah). Dikirim sebagai notifikasi dalam
     * aplikasi ke nasabah aktif pada bank sampah tujuan (bank_sampah_id null = semua bank sampah, hanya Admin).
     * Pengumuman yang sudah terkirim tidak dapat diubah atau dihapus; yang terjadwal bisa dibatalkan.
     */
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('bank_sampah_id')->nullable()->constrained('bank_sampah');
            $table->string('judul', 120);
            $table->string('isi', 300);
            $table->timestamp('kirim_pada');
            $table->timestamp('terkirim_at')->nullable();
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->timestamps();
            $table->index(['terkirim_at', 'kirim_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
