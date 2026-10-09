<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aktivitas_biopori', function (Blueprint $table): void {
            $table->string('metode_pengolahan', 20)->default('biopori')->after('titik_biopori_id');
        });

        // Aktivitas lama: ikuti jenis lokasinya (BioporiPrint atau Biopori biasa).
        DB::table('aktivitas_biopori')
            ->whereIn('titik_biopori_id', DB::table('titik_biopori')->where('bioporiprint', true)->select('id'))
            ->update(['metode_pengolahan' => 'bioporiprint']);
    }

    public function down(): void
    {
        Schema::table('aktivitas_biopori', function (Blueprint $table): void {
            $table->dropColumn('metode_pengolahan');
        });
    }
};
