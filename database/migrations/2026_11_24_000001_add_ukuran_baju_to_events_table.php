<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilihan ukuran baju kini diatur per event oleh admin. Event yang sudah
     * menyediakan baju diisi daftar lama (XXS–XL) agar checkout-nya tidak berubah.
     */
    public function up(): void
    {
        if (! Schema::hasTable('events') || Schema::hasColumn('events', 'ukuran_baju')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->json('ukuran_baju')->nullable()->after('ada_baju');
        });

        DB::table('events')
            ->where('ada_baju', true)
            ->update(['ukuran_baju' => json_encode(['XXS', 'XS', 'S', 'M', 'L', 'XL'])]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'ukuran_baju')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('ukuran_baju');
            });
        }
    }
};
