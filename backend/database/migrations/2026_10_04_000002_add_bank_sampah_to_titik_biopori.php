<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titik_biopori', function (Blueprint $table): void {
            $table->foreignId('bank_sampah_id')->nullable()->after('nasabah_id')->constrained('bank_sampah')->nullOnDelete();
            $table->index(['bank_sampah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('titik_biopori', function (Blueprint $table): void {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropIndex(['bank_sampah_id', 'status']);
            $table->dropColumn('bank_sampah_id');
        });
    }
};
