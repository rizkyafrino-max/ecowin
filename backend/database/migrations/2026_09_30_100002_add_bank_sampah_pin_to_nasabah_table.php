<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->foreignId('bank_sampah_id')
                ->after('id')
                ->constrained('bank_sampah')
                ->onDelete('restrict');
            $table->string('pin')->after('nisn_atau_nik');
        });
    }

    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn(['bank_sampah_id', 'pin']);
        });
    }
};
