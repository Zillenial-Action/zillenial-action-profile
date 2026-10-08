<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Snapshot komisi saat checkout, supaya perubahan setting program
            // tidak mengubah komisi transaksi yang sudah terjadi.
            $table->unsignedInteger('komisi_fundraiser')->default(0)->after('total_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn('komisi_fundraiser');
        });
    }
};
