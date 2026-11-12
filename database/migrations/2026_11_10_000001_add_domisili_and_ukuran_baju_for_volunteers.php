<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Domisili melekat ke orangnya (volunteers), sedangkan ukuran baju bisa berbeda
     * per event sehingga disimpan di pivot transaksi_volunteers. Event menandai
     * apakah pesertanya mendapat baju lewat events.ada_baju.
     */
    public function up(): void
    {
        if (Schema::hasTable('events') && ! Schema::hasColumn('events', 'ada_baju')) {
            Schema::table('events', function (Blueprint $table) {
                $table->boolean('ada_baju')->default(false)->after('agenda');
            });
        }

        if (Schema::hasTable('volunteers') && ! Schema::hasColumn('volunteers', 'domisili')) {
            Schema::table('volunteers', function (Blueprint $table) {
                $table->string('domisili', 100)->nullable()->after('jenis_kelamin');
            });
        }

        if (Schema::hasTable('transaksi_volunteers') && ! Schema::hasColumn('transaksi_volunteers', 'ukuran_baju')) {
            Schema::table('transaksi_volunteers', function (Blueprint $table) {
                $table->string('ukuran_baju', 10)->nullable()->after('id_volunteer');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksi_volunteers', 'ukuran_baju')) {
            Schema::table('transaksi_volunteers', function (Blueprint $table) {
                $table->dropColumn('ukuran_baju');
            });
        }

        if (Schema::hasColumn('volunteers', 'domisili')) {
            Schema::table('volunteers', function (Blueprint $table) {
                $table->dropColumn('domisili');
            });
        }

        if (Schema::hasColumn('events', 'ada_baju')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('ada_baju');
            });
        }
    }
};
