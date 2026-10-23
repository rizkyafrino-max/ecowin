<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal pengumpulan sampah per bank sampah (diatur Admin). Pada hari jadwal, nasabah bank sampah itu otomatis menerima
     * pengingat sebelum kegiatan dimulai. terakhir_kirim_tanggal mencegah pengingat terkirim dua kali dalam sehari.
     */
    public function up(): void
    {
        Schema::create('jadwal_pengumpulan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->cascadeOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->json('hari');                       // hari ISO 1=Senin ... 7=Minggu
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->string('lokasi', 120)->nullable();
            $table->string('catatan', 200)->nullable();
            $table->unsignedSmallInteger('pengingat_menit')->default(120); // kirim sekian menit sebelum jam mulai
            $table->boolean('aktif')->default(true);
            $table->date('terakhir_kirim_tanggal')->nullable();
            $table->timestamps();
            $table->index(['aktif', 'bank_sampah_id']);
        });

        Schema::table('pengumuman', function (Blueprint $table): void {
            $table->foreignId('jadwal_pengumpulan_id')->nullable()->after('bank_sampah_id')->constrained('jadwal_pengumpulan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengumuman', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('jadwal_pengumpulan_id');
        });
        Schema::dropIfExists('jadwal_pengumpulan');
    }
};
