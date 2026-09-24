<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'image'       => $this->image ? url('/' . $this->image) : null,
            'harga'       => $this->harga,
            'waktu_mulai' => $this->waktu_mulai?->toIso8601String(),
            'kota'        => $this->kota,
            'mitra'       => $this->mitra,
            'status'      => $this->isActive(),
            'sisa_tiket'  => $this->remainingTickets(),
            'is_sold_out' => $this->isSoldOut(),
        ];
    }
}
