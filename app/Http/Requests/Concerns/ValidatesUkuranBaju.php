<?php

namespace App\Http\Requests\Concerns;

use App\Models\Event;

/**
 * Validasi pilihan ukuran baju di form event (tambah & edit).
 */
trait ValidatesUkuranBaju
{
    /**
     * Gabungkan checklist ukuran_baju[] dan teks ukuran_baju_lain (dipisah koma) jadi satu daftar.
     * Ukuran preset ditulis sesuai ejaan baku dan diurutkan lebih dulu; ukuran lain mengikuti urutan input.
     */
    protected function prepareUkuranBaju(): void
    {
        $dipilih = $this->input('ukuran_baju');
        $lain = preg_split('/[,\r\n]+/', (string) $this->input('ukuran_baju_lain', '')) ?: [];

        $baku = [];
        foreach (Event::UKURAN_BAJU_PRESET as $preset) {
            $baku[strtoupper($preset)] = $preset;
        }

        $ukuran = [];
        foreach ([...(is_array($dipilih) ? $dipilih : []), ...$lain] as $value) {
            if (! is_scalar($value) || ($value = trim((string) $value)) === '') {
                continue;
            }

            $kunci = strtoupper($value);
            $ukuran[$kunci] ??= $baku[$kunci] ?? $value;
        }

        $urutanPreset = array_flip(array_keys($baku));
        uksort($ukuran, fn ($a, $b) => ($urutanPreset[$a] ?? PHP_INT_MAX) <=> ($urutanPreset[$b] ?? PHP_INT_MAX));

        $this->merge(['ukuran_baju' => array_values($ukuran)]);
    }

    /**
     * @return array<string, string>
     */
    protected function ukuranBajuRules(): array
    {
        return [
            // Pivot transaksi_volunteers.ukuran_baju bertipe string(10).
            'ukuran_baju' => 'exclude_unless:ada_baju,1|required|array|max:20',
            'ukuran_baju.*' => 'string|max:10',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function ukuranBajuMessages(): array
    {
        return [
            'ukuran_baju.required' => 'Pilih minimal satu ukuran baju.',
            'ukuran_baju.max' => 'Maksimal 20 ukuran baju.',
            'ukuran_baju.*.max' => 'Ukuran baju ":input" terlalu panjang (maksimal 10 karakter).',
        ];
    }
}
