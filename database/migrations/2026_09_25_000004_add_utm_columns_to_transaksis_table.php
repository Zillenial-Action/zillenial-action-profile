<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Last touch disalin ke kolom sendiri supaya laporan bisa group by + index.
            $table->string('utm_source', 191)->nullable()->after('id_customer');
            $table->string('utm_medium', 191)->nullable()->after('utm_source');
            $table->string('utm_campaign', 191)->nullable()->after('utm_medium');
            // Semua parameter utm_* (first & last touch), landing page, dan referrer.
            $table->json('utm_data')->nullable()->after('utm_campaign');

            $table->index(['utm_source', 'utm_medium', 'utm_campaign']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropIndex(['utm_source', 'utm_medium', 'utm_campaign']);
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'utm_data']);
        });
    }
};
