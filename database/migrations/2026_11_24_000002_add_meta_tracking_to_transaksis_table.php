<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Snapshot saat checkout (fbp, fbc, ip, user_agent, source_url): event Purchase
            // dikirim belakangan dari webhook Midtrans, saat browser pembeli sudah tidak ada.
            if (! Schema::hasColumn('transaksis', 'meta_tracking')) {
                $table->json('meta_tracking')->nullable()->after('utm_data');
            }
            if (! Schema::hasColumn('transaksis', 'meta_purchase_sent_at')) {
                $table->timestamp('meta_purchase_sent_at')->nullable()->after('meta_tracking');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            foreach (['meta_purchase_sent_at', 'meta_tracking'] as $column) {
                if (Schema::hasColumn('transaksis', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
