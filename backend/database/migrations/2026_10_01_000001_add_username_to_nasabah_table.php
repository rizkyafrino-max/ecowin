<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->unique()->after('id');
        });

        DB::table('nasabah')->whereNull('username')->orderBy('id')->eachById(function (object $nasabah): void {
            DB::table('nasabah')->where('id', $nasabah->id)->update(['username' => 'nasabah'.$nasabah->id]);
        });
    }

    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
