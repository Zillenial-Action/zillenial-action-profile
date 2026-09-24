<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transaksis', 'public_token')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->string('public_token', 64)->nullable()->after('invoice')->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksis', 'public_token')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropUnique(['public_token']);
                $table->dropColumn('public_token');
            });
        }
    }
};
