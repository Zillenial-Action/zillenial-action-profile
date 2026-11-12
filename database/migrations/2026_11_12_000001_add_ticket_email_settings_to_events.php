<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan email tiket & kontak footer tiket khusus per event.
     * Kolom kosong = pakai nilai default dari menu Pengaturan Tiket.
     */
    private const COLUMNS = [
        'pesan_email_tiket',
        'kontak_whatsapp_tiket',
        'kontak_email_tiket',
        'kontak_website_tiket',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'pesan_email_tiket')) {
                $table->longText('pesan_email_tiket')->nullable()->after('syarat_tiket');
            }
            if (! Schema::hasColumn('events', 'kontak_whatsapp_tiket')) {
                $table->string('kontak_whatsapp_tiket', 20)->nullable()->after('pesan_email_tiket');
            }
            if (! Schema::hasColumn('events', 'kontak_email_tiket')) {
                $table->string('kontak_email_tiket')->nullable()->after('kontak_whatsapp_tiket');
            }
            if (! Schema::hasColumn('events', 'kontak_website_tiket')) {
                $table->string('kontak_website_tiket')->nullable()->after('kontak_email_tiket');
            }
        });
    }

    public function down(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn ($c) => Schema::hasColumn('events', $c)));

        if ($existing !== []) {
            Schema::table('events', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
