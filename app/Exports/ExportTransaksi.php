<?php

namespace App\Exports;

use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportTransaksi implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        return Transaksi::with(['event:id,name', 'payment:id,name'])
            ->select([
                'id', 'id_event', 'id_payment', 'invoice', 'name',
                'email', 'telepon', 'status_pembayaran',
                'tanggal_register', 'tanggal_pembayaran', 'created_at',
            ])
            ->orderByDesc('created_at')
            ->when(
                ! empty($this->filters['tanggal_awal']) && ! empty($this->filters['tanggal_akhir']),
                fn ($q) => $q->whereDate('created_at', '>=', $this->filters['tanggal_awal'])
                             ->whereDate('created_at', '<=', $this->filters['tanggal_akhir'])
            )
            ->when(
                ! empty($this->filters['tanggal_awal']) && empty($this->filters['tanggal_akhir']),
                fn ($q) => $q->whereDate('created_at', $this->filters['tanggal_awal'])
            )
            ->when(
                ! empty($this->filters['id_event']),
                fn ($q) => $q->where('id_event', $this->filters['id_event'])
            )
            ->when(
                ! empty($this->filters['status_pembayaran']),
                fn ($q) => $q->where('status_pembayaran', $this->filters['status_pembayaran'])
            )
            ->when(
                ! empty($this->filters['id_payment']),
                fn ($q) => $q->where('id_payment', $this->filters['id_payment'])
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
            'Id Event',
            'Event',
            'Invoice',
            'Name',
            'Email',
            'Telepon',
            'Status Pembayaran',
            'Tanggal Register',
            'Tanggal Pembayaran',
            'Payment',
            'Tanggal di buat',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->id_event,
            $row->event?->name,
            $row->invoice,
            $row->name,
            $row->email,
            $row->telepon,
            $row->status_pembayaran,
            $row->tanggal_register?->format('d-m-y h:i A'),
            $row->tanggal_pembayaran?->format('d-m-y h:i A'),
            $row->payment?->name,
            $row->created_at->format('d-m-Y h:i A'),
        ];
    }
}
