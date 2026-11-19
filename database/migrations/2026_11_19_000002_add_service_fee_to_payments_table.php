<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biaya layanan per metode pembayaran: none, percent (% dari subtotal) atau fixed (rupiah per tiket).
     */
    public function up(): void
    {
        if (Schema::hasColumn('payments', 'biaya_layanan_tipe')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('biaya_layanan_tipe', 10)->default('none')->after('midtrans_bank');
            $table->decimal('biaya_layanan_nilai', 12, 2)->default(0)->after('biaya_layanan_tipe');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payments', 'biaya_layanan_tipe')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['biaya_layanan_tipe', 'biaya_layanan_nilai']);
        });
    }
};
