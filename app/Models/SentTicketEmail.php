<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catatan satu email tiket yang berhasil terkirim.
 * Ditulis oleh App\Listeners\RecordSentTicketEmail; ditampilkan di menu "Email Tiket Terkirim".
 */
class SentTicketEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice',
        'recipient',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
