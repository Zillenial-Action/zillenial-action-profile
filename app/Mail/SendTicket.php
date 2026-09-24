<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SendTicket extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Jumlah percobaan sebelum job dianggap gagal permanen (masuk failed_jobs).
     */
    public int $tries = 5;

    /**
     * Jeda antar percobaan (detik): 1 menit, 5 menit, 15 menit, lalu 15 menit.
     * Memberi waktu pulih untuk gangguan SMTP sementara.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public $transactionId;
    public $ticketUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($transactionId, $ticketUrl) // Tambahkan parameter di konstruktor
    {
        $this->transactionId = $transactionId;
        $this->ticketUrl = $ticketUrl;
    }

    /**
     * Berhenti mencoba setelah 1 jam meski attempt masih tersisa, agar tiket tidak
     * terkirim jauh terlambat dan job tidak menumpuk di antrean.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHour();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tiket Event',
        );
    }

    /**
     * Header khusus penanda email tiket. Dipakai listener RecordSentTicketEmail untuk
     * mengenali email tiket yang berhasil terkirim dan mencatat invoice-nya.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Ticket-Invoice' => (string) $this->transactionId,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'tiket.index',
            with: [ // Teruskan data ke view
                'transactionId' => $this->transactionId,
                'ticketUrl' => $this->ticketUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
