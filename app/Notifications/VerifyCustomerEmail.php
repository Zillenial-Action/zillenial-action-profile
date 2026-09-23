<?php

namespace App\Notifications;

use App\Mail\VerifyCustomerEmail as VerifyCustomerEmailMailable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Mail\Mailable;

class VerifyCustomerEmail extends VerifyEmail
{
    /**
     * Kirim mailable bermerek Zillenial Action (Bahasa Indonesia) alih-alih
     * notifikasi "Verify Email Address" bawaan Laravel.
     */
    public function toMail($notifiable): Mailable
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new VerifyCustomerEmailMailable($notifiable->name, $verificationUrl))
            ->to($notifiable->getEmailForVerification());
    }
}
