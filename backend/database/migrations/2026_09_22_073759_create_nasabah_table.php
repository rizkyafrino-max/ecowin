<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nasabah', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp')->unique();
            $table->string('alamat_rt_rw');
            $table->string('nisn_atau_nik')->nullable();
            $table->string('kartu_qr_token', 64)->unique();
            $table->string('foto_ktp_kk_path')->nullable();
            $table->enum('status_verifikasi', ['pending', 'verified'])->default('pending');
            $table->foreignId('dibuat_oleh')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nasabah');
    }
};
