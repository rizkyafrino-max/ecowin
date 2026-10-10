<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_anorganik', function (Blueprint $table): void {
            $table->index(['nasabah_id', 'created_at']);
            $table->index(['bank_sampah_id', 'created_at']);
        });
        Schema::table('transaksi_organik', function (Blueprint $table): void {
            $table->index(['nasabah_id', 'created_at']);
            $table->index(['bank_sampah_id', 'created_at']);
        });
        Schema::table('aktivitas_biopori', function (Blueprint $table): void {
            $table->index(['nasabah_id', 'status']);
            $table->index(['bank_sampah_id', 'status']);
        });
        Schema::table('penarikan_saldo', function (Blueprint $table): void {
            $table->index(['nasabah_id', 'status']);
            $table->index(['bank_sampah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_anorganik', function (Blueprint $table): void {
            $table->dropIndex(['nasabah_id', 'created_at']);
            $table->dropIndex(['bank_sampah_id', 'created_at']);
        });
        Schema::table('transaksi_organik', function (Blueprint $table): void {
            $table->dropIndex(['nasabah_id', 'created_at']);
            $table->dropIndex(['bank_sampah_id', 'created_at']);
        });
        Schema::table('aktivitas_biopori', function (Blueprint $table): void {
            $table->dropIndex(['nasabah_id', 'status']);
            $table->dropIndex(['bank_sampah_id', 'status']);
        });
        Schema::table('penarikan_saldo', function (Blueprint $table): void {
            $table->dropIndex(['nasabah_id', 'status']);
            $table->dropIndex(['bank_sampah_id', 'status']);
        });
    }
};
