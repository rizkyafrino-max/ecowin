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
        Schema::create('titik_biopori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nasabah_id')->nullable()->constrained('nasabah')->onDelete('restrict');
            $table->string('alamat_rt_rw');
            $table->string('deskripsi_lokasi')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('tanggal_tanam');
            $table->unsignedSmallInteger('jumlah_pipa')->default(1);
            $table->enum('status', ['aktif', 'penuh', 'dikosongkan'])->default('aktif');
            $table->string('foto_path')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titik_biopori');
    }
};
