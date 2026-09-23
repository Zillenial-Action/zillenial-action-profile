<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Renames foreign key columns to use id_tablename convention.
     * Uses raw SQL for renaming to avoid foreign key constraint issues.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Rename columns in kode_vouchers table
        if (Schema::hasColumn('kode_vouchers', 'event_id')) {
            Schema::table('kode_vouchers', function (Blueprint $table) {
                $table->renameColumn('event_id', 'id_event');
            });
        }

        // Rename columns in pixels table and add pixel_code column
        if (Schema::hasColumn('pixels', 'event_id')) {
            Schema::table('pixels', function (Blueprint $table) {
                $table->renameColumn('event_id', 'id_event');
            });
        }
        
        if (!Schema::hasColumn('pixels', 'pixel_code')) {
            Schema::table('pixels', function (Blueprint $table) {
                $table->string('pixel_code')->after('type')->nullable()->comment('Pixel tracking ID from Meta/TikTok');
            });
        }

        // Rename columns in transaksis table
        if (Schema::hasColumn('transaksis', 'event_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->renameColumn('event_id', 'id_event');
            });
        }
        
        if (Schema::hasColumn('transaksis', 'payment_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->renameColumn('payment_id', 'id_payment');
            });
        }
        
        if (Schema::hasColumn('transaksis', 'voucher_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->renameColumn('voucher_id', 'id_voucher');
            });
        }

        // Rename columns in transaksi_volunteers table
        if (Schema::hasColumn('transaksi_volunteers', 'volunteer_id')) {
            Schema::table('transaksi_volunteers', function (Blueprint $table) {
                $table->renameColumn('volunteer_id', 'id_volunteer');
            });
        }
        
        if (Schema::hasColumn('transaksi_volunteers', 'transaksi_id')) {
            Schema::table('transaksi_volunteers', function (Blueprint $table) {
                $table->renameColumn('transaksi_id', 'id_transaksi');
            });
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Revert transaksi_volunteers
        if (Schema::hasColumn('transaksi_volunteers', 'id_volunteer')) {
            Schema::table('transaksi_volunteers', function (Blueprint $table) {
                $table->renameColumn('id_volunteer', 'volunteer_id');
            });
        }
        
        if (Schema::hasColumn('transaksi_volunteers', 'id_transaksi')) {
            Schema::table('transaksi_volunteers', function (Blueprint $table) {
                $table->renameColumn('id_transaksi', 'transaksi_id');
            });
        }

        // Revert transaksis
        if (Schema::hasColumn('transaksis', 'id_event')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->renameColumn('id_event', 'event_id');
            });
        }
        
        if (Schema::hasColumn('transaksis', 'id_payment')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->renameColumn('id_payment', 'payment_id');
            });
        }
        
        if (Schema::hasColumn('transaksis', 'id_voucher')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->renameColumn('id_voucher', 'voucher_id');
            });
        }

        // Revert pixels
        if (Schema::hasColumn('pixels', 'id_event')) {
            Schema::table('pixels', function (Blueprint $table) {
                $table->renameColumn('id_event', 'event_id');
            });
        }
        
        if (Schema::hasColumn('pixels', 'pixel_code')) {
            Schema::table('pixels', function (Blueprint $table) {
                $table->dropColumn('pixel_code');
            });
        }

        // Revert kode_vouchers
        if (Schema::hasColumn('kode_vouchers', 'id_event')) {
            Schema::table('kode_vouchers', function (Blueprint $table) {
                $table->renameColumn('id_event', 'event_id');
            });
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
