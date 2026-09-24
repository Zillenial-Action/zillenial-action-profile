<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tambah kolom payment_instructions untuk menyimpan hasil charge Core API
     * (nomor VA, QR, deeplink, dll) yang dipakai untuk render UI pembayaran sendiri.
     */
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->json('payment_instructions')->nullable()->after('snap_token')
                  ->comment('Hasil ternormalisasi dari Midtrans Core API charge()');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn('payment_instructions');
        });
    }
};
