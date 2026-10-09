<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori (anorganik/organik), status jenis sampah, dan harga bertingkat
 * berdasarkan rentang berat + periode berlaku.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_sampah', function (Blueprint $table): void {
            $table->string('tipe', 20)->default('anorganik')->after('nama_kategori')->index();
        });

        Schema::table('jenis_sampah', function (Blueprint $table): void {
            $table->string('satuan', 20)->default('kg')->after('nama_jenis');
            $table->string('status', 20)->default('aktif')->after('satuan');
        });

        Schema::table('harga_sampah', function (Blueprint $table): void {
            $table->string('kondisi')->default('Utuh')->change();
            $table->decimal('minimal_berat', 10, 2)->default(0)->after('kondisi');
            $table->decimal('maksimal_berat', 10, 2)->nullable()->after('minimal_berat');
            $table->timestamp('berlaku_sampai')->nullable()->after('berlaku_mulai');
            $table->string('status', 20)->default('aktif')->after('berlaku_sampai');
            $table->index(['jenis_sampah_id', 'status', 'berlaku_mulai']);
        });
    }

    public function down(): void
    {
        Schema::table('harga_sampah', function (Blueprint $table): void {
            $table->dropIndex(['jenis_sampah_id', 'status', 'berlaku_mulai']);
            $table->dropColumn(['minimal_berat', 'maksimal_berat', 'berlaku_sampai', 'status']);
        });
        Schema::table('jenis_sampah', function (Blueprint $table): void {
            $table->dropColumn(['satuan', 'status']);
        });
        Schema::table('kategori_sampah', function (Blueprint $table): void {
            $table->dropIndex(['tipe']);
            $table->dropColumn('tipe');
        });
    }
};
