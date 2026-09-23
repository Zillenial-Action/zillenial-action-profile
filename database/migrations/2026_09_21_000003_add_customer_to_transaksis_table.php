<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transaksis', 'id_customer')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('id_customer')
                    ->nullable()
                    ->after('id_voucher')
                    ->constrained('customers')
                    ->nullOnDelete();
                $table->index('id_customer');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksis', 'id_customer')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropForeign(['id_customer']);
                $table->dropIndex(['id_customer']);
                $table->dropColumn('id_customer');
            });
        }
    }
};
