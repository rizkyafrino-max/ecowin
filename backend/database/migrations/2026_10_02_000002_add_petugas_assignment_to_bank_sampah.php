<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A petugas assignment is stored on users.bank_sampah_id; no duplicate column is needed here.
    }

    public function down(): void
    {
        // Nothing to reverse: this migration intentionally adds no schema.
    }
};
