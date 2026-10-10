<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Verifikasi OTP WhatsApp dibatalkan; kolom penandanya tidak dipakai lagi. */
    public function up(): void
    {
        if (Schema::hasColumn('nasabah', 'no_hp_verified_at')) {
            Schema::table('nasabah', function (Blueprint $table): void {
                $table->dropColumn('no_hp_verified_at');
            });
        }
    }

    public function down(): void {}
};
