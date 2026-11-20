<?php

namespace App\Http\Requests\Concerns;

use App\Models\Payment;
use Illuminate\Validation\Rule;

/**
 * Validasi biaya layanan di form metode pembayaran (tambah & edit).
 */
trait ValidatesServiceFee
{
    protected function prepareServiceFee(): void
    {
        $tipe = $this->input('biaya_layanan_tipe', Payment::FEE_NONE);

        $this->merge([
            'biaya_layanan_tipe' => $tipe,
            // Satu kolom nilai di DB; form punya input terpisah untuk persen dan nominal.
            'biaya_layanan_nilai' => match ($tipe) {
                Payment::FEE_PERCENT => $this->input('biaya_layanan_persen'),
                Payment::FEE_FIXED => $this->input('biaya_layanan_nominal'),
                default => 0,
            },
        ]);
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function serviceFeeRules(): array
    {
        $nilai = match ($this->input('biaya_layanan_tipe')) {
            Payment::FEE_PERCENT => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            Payment::FEE_FIXED => ['required', 'integer', 'min:1', 'max:10000000'],
            default => ['numeric'],
        };

        return [
            'biaya_layanan_tipe' => ['required', Rule::in(Payment::FEE_TYPES)],
            'biaya_layanan_nilai' => $nilai,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function serviceFeeMessages(): array
    {
        $persen = $this->input('biaya_layanan_tipe') === Payment::FEE_PERCENT;

        return [
            'biaya_layanan_tipe.in' => 'Jenis biaya layanan tidak valid.',
            'biaya_layanan_nilai.required' => $persen ? 'Persentase biaya layanan wajib diisi.' : 'Nominal biaya layanan wajib diisi.',
            'biaya_layanan_nilai.gt' => 'Persentase harus lebih dari 0.',
            'biaya_layanan_nilai.max' => $persen ? 'Persentase maksimal 100%.' : 'Nominal terlalu besar.',
            'biaya_layanan_nilai.decimal' => 'Persentase maksimal 2 angka di belakang koma.',
            'biaya_layanan_nilai.integer' => 'Nominal harus berupa angka rupiah tanpa koma.',
            'biaya_layanan_nilai.min' => 'Nominal minimal Rp1.',
            'biaya_layanan_nilai.numeric' => $persen ? 'Persentase harus berupa angka.' : 'Nominal harus berupa angka.',
        ];
    }
}
