<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'invoice'           => $this->invoice,
            'status_pembayaran' => $this->status_pembayaran,
            'total_pembayaran'  => $this->total_pembayaran,
            'jumlah_tiket'      => $this->jumlah_tiket,
            'payment_channel'      => $this->payment?->midtrans_payment_type,
            'payment_instructions' => $this->payment_instructions,
            'event'             => $this->whenLoaded('event', fn () => [
                'name'        => $this->event->name,
                'slug'        => $this->event->slug,
                'waktu_mulai' => $this->event->waktu_mulai?->toIso8601String(),
                'nama_tempat' => $this->event->nama_tempat,
                'image'       => $this->event->image ? url('/' . $this->event->image) : null,
            ]),
        ];
    }
}
