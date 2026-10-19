<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifikasi dalam aplikasi untuk nasabah (keputusan penarikan, verifikasi, setoran tercatat, dst.).
     * Dibersihkan otomatis setelah 90 hari (ecowin:bersihkan-notifikasi).
     */
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipe', 40);
            $table->string('judul', 120);
            $table->string('isi', 300);
            $table->string('aksi', 30)->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'dibaca_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
