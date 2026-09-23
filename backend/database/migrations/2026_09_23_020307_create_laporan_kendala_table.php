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
        Schema::create('laporan_kendala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dilaporkan_oleh_nasabah_id')->nullable()->constrained('nasabah')->onDelete('set null');
            $table->foreignId('dilaporkan_oleh_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('kategori', ['pencatatan', 'bug']);
            $table->text('deskripsi');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->onDelete('restrict');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_kendala');
    }
};
