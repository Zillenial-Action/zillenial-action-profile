<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * settings: pengaturan global key-value (mis. S&K default tiket, kontak footer tiket).
     * events.syarat_tiket: S&K tiket khusus event; kosong = pakai default dari settings.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('events') && ! Schema::hasColumn('events', 'syarat_tiket')) {
            Schema::table('events', function (Blueprint $table) {
                $table->longText('syarat_tiket')->nullable()->after('deskripsi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'syarat_tiket')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('syarat_tiket');
            });
        }

        Schema::dropIfExists('settings');
    }
};
