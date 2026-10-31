<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto bukti opsional pada laporan (mis. tangkapan layar bug). Hanya jalur berkas di disk privat yang disimpan.
     */
    public function up(): void
    {
        Schema::table('laporan_kendala', function (Blueprint $table) {
            $table->string('foto_bukti_path')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendala', function (Blueprint $table) {
            $table->dropColumn('foto_bukti_path');
        });
    }
};
