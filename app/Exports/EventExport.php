<?php

namespace App\Exports;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EventExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        return Event::select([
                'id', 'name', 'mitra', 'website', 'status',
                'waktu_mulai', 'waktu_berakhir', 'nama_tempat',
                'alamat', 'kota', 'jumlah_tiket', 'harga', 'created_at',
            ])
            ->orderByDesc('created_at')
            ->when(
                ! empty($this->filters['waktu_awal']) && ! empty($this->filters['waktu_akhir']),
                fn ($q) => $q->whereDate('created_at', '>=', $this->filters['waktu_awal'])
                             ->whereDate('created_at', '<=', $this->filters['waktu_akhir'])
            )
            ->when(
                ! empty($this->filters['waktu_awal']) && empty($this->filters['waktu_akhir']),
                fn ($q) => $q->whereDate('created_at', $this->filters['waktu_awal'])
            )
            ->when(
                ! empty($this->filters['mitra']),
                fn ($q) => $q->where('mitra', 'like', '%' . $this->filters['mitra'] . '%')
            )
            ->when(
                isset($this->filters['status']) && $this->filters['status'] !== '',
                fn ($q) => $q->where('status', $this->filters['status'])
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
            'Nama Event',
            'Mitra',
            'Website',
            'Status',
            'Waktu Mulai',
            'Waktu Berakhir',
            'Nama Tempat',
            'Alamat',
            'Kota',
            'Jumlah Tiket',
            'Harga',
            'Tanggal di buat',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->mitra,
            $row->website,
            $row->status ? 'Aktif' : 'Tidak Aktif',
            $row->waktu_mulai->format('d-m-Y'),
            $row->waktu_berakhir->format('d-m-Y'),
            $row->nama_tempat,
            $row->alamat,
            $row->kota,
            $row->jumlah_tiket,
            $row->harga,
            $row->created_at->format('d-m-Y h:i A'),
        ];
    }
}
