<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simpan data pengunjung mentah dari checkout sebelum pembayaran sukses.
     * Volunteer & pivot baru dibuat dari kolom ini saat status_pembayaran = Success,
     * supaya data personal tidak pernah tersimpan permanen untuk transaksi gagal/expired.
     */
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->json('pengunjung_data')->nullable()->after('payment_instructions')
                  ->comment('Data pengunjung mentah dari checkout, dipakai membuat volunteer saat Success');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn('pengunjung_data');
        });
    }
};
