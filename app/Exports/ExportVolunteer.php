<?php

namespace App\Exports;

use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportVolunteer implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        return Volunteer::withCount('transaksis')
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
            $row->transaksis_count,
            $row->created_at?->format('d-m-Y H:i'),
        ];
    }
}
