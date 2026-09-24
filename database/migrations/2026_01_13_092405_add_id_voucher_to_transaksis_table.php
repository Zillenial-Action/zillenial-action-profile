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
        // Kolom bisa sudah ada bila migrasi rename (voucher_id -> id_voucher) sudah dijalankan.
        // Guard agar migrate:fresh dari nol tidak gagal karena kolom duplikat.
        if (Schema::hasColumn('transaksis', 'id_voucher')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            $table->foreignId('id_voucher')
                  ->nullable()
                  ->after('id_payment')
                  ->constrained('kode_vouchers')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('transaksis', 'id_voucher')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['id_voucher']);
            $table->dropColumn('id_voucher');
        });
    }
};
