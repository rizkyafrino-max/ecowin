<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nasabah yang mendaftar sendiri (lewat Google) tidak dibuat oleh Admin/Petugas,
 * sehingga `dibuat_oleh` boleh kosong. Status verifikasi awalnya `pending`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->unsignedBigInteger('dibuat_oleh')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan ke NOT NULL: baris pendaftaran mandiri akan melanggar.
    }
};
