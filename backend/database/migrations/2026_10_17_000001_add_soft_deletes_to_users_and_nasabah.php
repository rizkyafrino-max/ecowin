<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penghapusan data pengguna/nasabah tidak aktif bersifat "hapus data pribadi + sembunyikan":
     * baris tetap ada agar riwayat transaksi dan audit log tidak putus, tetapi tidak tampil lagi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes();
        });
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
