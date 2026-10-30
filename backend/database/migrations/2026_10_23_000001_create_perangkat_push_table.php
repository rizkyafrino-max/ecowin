<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Token FCM perangkat nasabah (untuk "membangunkan" aplikasi saat ada notifikasi baru).
     * Token disimpan terenkripsi; token_hash (SHA-256) hanya untuk pencarian/keunikan. Satu token hanya boleh dimiliki
     * satu akun: bila perangkat yang sama dipakai akun lain, kepemilikan berpindah.
     */
    public function up(): void
    {
        Schema::create('perangkat_push', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->text('token');
            $table->string('platform', 10)->default('android');
            $table->timestamp('terakhir_aktif_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat_push');
    }
};
