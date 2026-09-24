<?php

namespace App\Listeners;

use App\Models\SentTicketEmail;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;

/**
 * Catat email tiket yang BERHASIL terkirim ke tabel sent_ticket_emails.
 *
 * Hanya memproses pesan yang membawa header X-Ticket-Invoice (ditambahkan oleh App\Mail\SendTicket),
 * sehingga email non-tiket (mis. verifikasi) diabaikan. Berjalan di proses manapun email dikirim
 * (queue worker untuk jalur webhook, atau web untuk kirim manual/ubah status admin).
 */
class RecordSentTicketEmail
{
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->sent->getSymfonySentMessage()->getOriginalMessage();

            if (! $message instanceof Email) {
                return;
            }

            $headers = $message->getHeaders();

            if (! $headers->has('X-Ticket-Invoice')) {
                return;
            }

            $invoice = trim($headers->get('X-Ticket-Invoice')->getBodyAsString());

            foreach ($message->getTo() as $address) {
                SentTicketEmail::create([
                    'invoice' => $invoice,
                    'recipient' => $address->getAddress(),
                    'sent_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Pencatatan tidak boleh menggagalkan pengiriman email yang sudah sukses.
            Log::warning('Gagal mencatat email tiket terkirim', ['error' => $e->getMessage()]);
        }
    }
}
