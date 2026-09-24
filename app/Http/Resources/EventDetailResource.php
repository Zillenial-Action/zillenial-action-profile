<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'image'          => $this->image ? url('/' . $this->image) : null,
            'harga'          => $this->harga,
            'waktu_mulai'    => $this->waktu_mulai?->toIso8601String(),
            'waktu_berakhir' => $this->waktu_berakhir?->toIso8601String(),
            'kota'           => $this->kota,
            'mitra'          => $this->mitra,
            'website'        => $this->website,
            'deskripsi'      => $this->deskripsi,
            'benefits'       => array_values($this->benefits ?? []),
            'agenda'         => array_values($this->agenda ?? []),
            'nama_tempat'    => $this->nama_tempat,
            'alamat'         => $this->alamat,
            'direction_url'  => $this->direction,
            'jumlah_tiket'   => $this->jumlah_tiket,
            'status'         => $this->isActive(),
            'sisa_tiket'     => $this->remainingTickets(),
            'is_sold_out'    => $this->isSoldOut(),
        ];
    }
}
