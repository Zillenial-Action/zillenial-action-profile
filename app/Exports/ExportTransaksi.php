<?php

namespace App\Exports;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// WithStrictNullComparison: tanpa ini fromArray melewati nilai 0, sehingga total Rp0 jadi kosong.
class ExportTransaksi implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStrictNullComparison
{
    private const FORMAT_RUPIAH = '#,##0';

    private const FORMAT_TANGGAL = 'dd-mm-yyyy hh:mm';

    public function __construct(protected Builder $query) {}

    public function query(): Builder
    {
        return $this->query
            ->with([
                // Event/payment/voucher yang sudah di-soft-delete tetap harus terbaca di laporan.
                'event' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'payment' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'voucher' => fn ($q) => $q->withTrashed()->select('id', 'kode', 'nilai_diskon'),
                'volunteers:id,name,telepon',
            ])
            ->orderByDesc('created_at')
            // Tie-breaker: created_at bisa kembar, dan urutan tak stabil membuat baris hilang antar-chunk.
            ->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Event',
            'Nama Pembeli',
            'Email Pembeli',
            'Telepon Pembeli',
            'Volunteer',
            'Jumlah Tiket',
            'Kode Voucher',
            'Potongan Voucher (Pertiket)',
            'Total Pembayaran',
            'Tanggal Register',
            'Status Pembayaran',
            'Tanggal Pembayaran',
            'Metode Pembayaran',
        ];
    }

    public function map($row): array
    {
        return [
            $row->invoice,
            $row->event?->name,
            $row->name,
            $row->email,
            $row->telepon,
            $row->volunteers->map(fn ($volunteer) => "{$volunteer->name} - {$volunteer->telepon}")->implode(', '),
            $row->jumlah_tiket,
            $row->voucher?->kode,
            $row->voucher?->nilai_diskon,
            $row->total_pembayaran,
            $this->toExcelDate($row->tanggal_register),
            $row->status_pembayaran,
            $this->toExcelDate($row->tanggal_pembayaran),
            $row->payment?->name,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => self::FORMAT_RUPIAH,
            'J' => self::FORMAT_RUPIAH,
            'K' => self::FORMAT_TANGGAL,
            'M' => self::FORMAT_TANGGAL,
        ];
    }

    /**
     * Hanya int/float yang menjadi angka; selain itu selalu teks.
     *
     * Binder default mengubah teks berawalan "=" menjadi formula (nama/email berasal dari
     * checkout publik, jadi bisa disisipi formula) dan telepon "628..." menjadi angka.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        $type = is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING;

        $cell->setValueExplicit($value, $type);

        return true;
    }

    private function toExcelDate(?DateTimeInterface $date): ?float
    {
        return $date ? (float) Date::dateTimeToExcel($date) : null;
    }
}
