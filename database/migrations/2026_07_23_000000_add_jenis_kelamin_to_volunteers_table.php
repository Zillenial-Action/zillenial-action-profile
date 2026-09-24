<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('volunteers') && !Schema::hasColumn('volunteers', 'jenis_kelamin')) {
            Schema::table('volunteers', function (Blueprint $table) {
                $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->after('telepon');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('volunteers', 'jenis_kelamin')) {
            Schema::table('volunteers', function (Blueprint $table) {
                $table->dropColumn('jenis_kelamin');
            });
        }
    }
};