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
        Schema::create('transaksi_organik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nasabah_id')->constrained('nasabah')->onDelete('restrict');
            $table->string('jenis_organik');
            $table->decimal('berat_kg', 8, 2);
            $table->boolean('checklist_bebas_plastik');
            $table->boolean('checklist_bebas_logam');
            $table->decimal('estimasi_kompos_kg', 8, 2);
            $table->foreignId('dicatat_oleh')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_organik');
    }
};
