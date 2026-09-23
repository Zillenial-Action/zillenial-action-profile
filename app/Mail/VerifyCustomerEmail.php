<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyCustomerEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $name;
    public string $verificationUrl;

    public function __construct(string $name, string $verificationUrl)
    {
        $this->name = $name;
        $this->verificationUrl = $verificationUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Email - Zillenial Action',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-customer',
            with: [
                'name' => $this->name,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }
}
