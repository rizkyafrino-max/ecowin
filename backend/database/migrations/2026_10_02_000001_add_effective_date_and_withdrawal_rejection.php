<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('harga_sampah', 'berlaku_mulai')) {
            Schema::table('harga_sampah', function (Blueprint $table) {
                $table->timestamp('berlaku_mulai')->useCurrent();
            });
        }

        $columns = Schema::getColumnListing('penarikan_saldo');
        if (in_array('status', $columns, true)) {
            Schema::table('penarikan_saldo', function (Blueprint $table) {
                $table->enum('status', ['pending', 'selesai', 'ditolak'])->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('penarikan_saldo', function (Blueprint $table) {
            $table->enum('status', ['pending', 'selesai'])->default('pending')->change();
        });

        if (Schema::hasColumn('harga_sampah', 'berlaku_mulai')) {
            Schema::table('harga_sampah', function (Blueprint $table) {
                $table->dropColumn('berlaku_mulai');
            });
        }
    }
};
