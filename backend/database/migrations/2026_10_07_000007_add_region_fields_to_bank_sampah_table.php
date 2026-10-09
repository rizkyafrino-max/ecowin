<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_sampah', function (Blueprint $table): void {
            $table->string('kode', 30)->nullable()->unique()->after('nama_bank_sampah');
            $table->string('kelurahan')->nullable()->after('alamat');
            $table->string('kecamatan')->nullable()->after('kelurahan');
            $table->string('kota')->nullable()->after('kecamatan');
        });

        DB::table('bank_sampah')->orderBy('id')->eachById(function (object $bank): void {
            DB::table('bank_sampah')->where('id', $bank->id)->update([
                'kode' => 'BS-'.str_pad((string) $bank->id, 4, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('bank_sampah', function (Blueprint $table): void {
            $table->dropUnique(['kode']);
            $table->dropColumn(['kode', 'kelurahan', 'kecamatan', 'kota']);
        });
    }
};
