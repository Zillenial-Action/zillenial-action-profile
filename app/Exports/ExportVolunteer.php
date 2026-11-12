<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportVolunteer implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected array $filters;

    protected ?Event $event = null;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        if (! empty($filters['event_id'])) {
            $this->event = Event::find($filters['event_id']);
        }
    }

    /**
     * Kolom ukuran baju hanya relevan saat export difilter ke satu event yang menyediakan baju.
     */
    protected function withUkuranBaju(): bool
    {
        return (bool) $this->event?->ada_baju;
    }

    public function query(): Builder
    {
        return Volunteer::withCount('transaksis')
            ->when($this->withUkuranBaju(), fn ($q) => $q->with(['transaksis' => fn ($query) =>
                $query->select('transaksis.id', 'transaksis.id_event')
                    ->where('id_event', $this->event->id)
                    ->where('status_pembayaran', 'Success')
            ]))
            ->orderByDesc('created_at')
            ->when(
                ! empty($this->filters['event_id']),
                fn ($q) => $q->whereHas('transaksis', fn ($query) =>
                    $query->where('id_event', $this->filters['event_id'])
                )
            );
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Id',
            'Nama',
            'Email',
            'Telepon',
            'Jenis Kelamin',
            'Domisili',
            ...($this->withUkuranBaju() ? ['Ukuran Baju'] : []),
            'Jumlah Transaksi',
            'Tanggal Daftar',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->email,
            $row->telepon,
            $row->jenis_kelamin ?? '-',
            $row->domisili ?? '-',
            ...($this->withUkuranBaju()
                ? [$row->transaksis->pluck('pivot.ukuran_baju')->filter()->implode(', ') ?: '-']
                : []),
            $row->transaksis_count,
            $row->created_at?->format('d-m-Y H:i'),
        ];
    }
}
