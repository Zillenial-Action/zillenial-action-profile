<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE payments MODIFY no_rek VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE payments SET no_rek = '' WHERE no_rek IS NULL");
        DB::statement('ALTER TABLE payments MODIFY no_rek VARCHAR(255) NOT NULL');
    }
};
