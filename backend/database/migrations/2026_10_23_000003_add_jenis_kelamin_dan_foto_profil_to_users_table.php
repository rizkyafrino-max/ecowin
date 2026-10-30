<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis kelamin (L/P) menentukan avatar bawaan; foto_profil adalah kode acak berkas foto unggahan pengguna
     * (disimpan privat dan disajikan lewat /foto/{kode}). Keduanya opsional; akun lama tetap valid.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->char('jenis_kelamin', 1)->nullable()->after('avatar');
            $table->string('foto_profil', 40)->nullable()->after('jenis_kelamin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['jenis_kelamin', 'foto_profil']);
        });
    }
};
