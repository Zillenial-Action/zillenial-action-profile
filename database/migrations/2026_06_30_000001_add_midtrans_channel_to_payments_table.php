<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tambah kolom channel Midtrans Core API agar tiap payment method bisa
     * mewakili satu channel spesifik (VA bank tertentu, QRIS, GoPay, dst).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('midtrans_payment_type')->nullable()->after('type')
                  ->comment('Core API payment_type: bank_transfer, echannel, gopay, shopeepay, qris');
            $table->string('midtrans_bank')->nullable()->after('midtrans_payment_type')
                  ->comment('Hanya untuk bank_transfer: bca, bni, bri, permata, cimb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['midtrans_payment_type', 'midtrans_bank']);
        });
    }
};
