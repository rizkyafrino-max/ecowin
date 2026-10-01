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
        Schema::create('transaksi_anorganik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nasabah_id')->constrained('nasabah')->onDelete('restrict');
            $table->foreignId('harga_sampah_id')->constrained('harga_sampah')->onDelete('restrict');
            $table->decimal('berat_kg', 8, 2);
            $table->unsignedBigInteger('nilai_rupiah');
            $table->foreignId('dicatat_oleh')->constrained('users')->onDelete('restrict');
            $table->string('foto_dokumentasi_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_anorganik');
    }
};
