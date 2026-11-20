<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian total transaksi: total_pembayaran = subtotal + biaya_layanan.
     * Tipe & nilai biaya layanan disimpan sebagai snapshot saat checkout, supaya perubahan
     * pengaturan admin tidak mengubah transaksi yang sudah dibuat.
     */
    public function up(): void
    {
        if (Schema::hasColumn('transaksis', 'subtotal')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal')->default(0)->after('jumlah_tiket');
            $table->unsignedBigInteger('biaya_layanan')->default(0)->after('subtotal');
            $table->string('biaya_layanan_tipe', 10)->nullable()->after('biaya_layanan');
            $table->decimal('biaya_layanan_nilai', 12, 2)->nullable()->after('biaya_layanan_tipe');
        });

        // Transaksi lama belum punya biaya layanan: seluruh total adalah harga tiket.
        DB::table('transaksis')->update(['subtotal' => DB::raw('total_pembayaran')]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transaksis', 'subtotal')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'biaya_layanan', 'biaya_layanan_tipe', 'biaya_layanan_nilai']);
        });
    }
};
