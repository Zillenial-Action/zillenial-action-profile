<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat email tiket yang BERHASIL terkirim (pasangan dari failed_jobs untuk yang gagal).
 * Diisi oleh listener App\Listeners\RecordSentTicketEmail saat event MessageSent membawa
 * header X-Ticket-Invoice — mencakup pengiriman via webhook (worker), ubah status admin,
 * dan kirim ulang manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_ticket_emails', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->index();
            $table->string('recipient');
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_ticket_emails');
    }
};
