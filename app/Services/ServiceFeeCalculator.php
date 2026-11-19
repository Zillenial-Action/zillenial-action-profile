<?php

namespace App\Services;

use App\Models\Payment;

/**
 * Biaya layanan checkout tiket, diatur per metode pembayaran (form Metode Pembayaran di admin).
 *
 * - percent: persen dari subtotal tiket setelah voucher, dibulatkan ke atas ke rupiah.
 * - fixed:   nominal rupiah per tiket.
 * - none:    tanpa biaya layanan.
 *
 * Rumus yang sama dipakai frontend untuk preview (zillenial-action-sostrip src/lib/serviceFee.ts);
 * ubah keduanya bersamaan.
 */
class ServiceFeeCalculator
{
    /**
     * @return array{amount: int, tipe: ?string, nilai: ?float}
     */
    public function calculate(Payment $payment, int $subtotal, int $jumlahTiket): array
    {
        ['tipe' => $tipe, 'nilai' => $nilai] = $payment->serviceFeeConfig();

        // Event gratis / voucher 100%: tidak ada biaya layanan.
        if ($tipe === Payment::FEE_NONE || $subtotal <= 0 || $jumlahTiket <= 0) {
            return ['amount' => 0, 'tipe' => null, 'nilai' => null];
        }

        $amount = match ($tipe) {
            // round() dulu supaya galat float (mis. 100000 * 1.1 / 100) tidak menambah Rp1.
            Payment::FEE_PERCENT => (int) ceil(round($subtotal * $nilai / 100, 6)),
            Payment::FEE_FIXED => (int) round($nilai) * $jumlahTiket,
        };

        return ['amount' => $amount, 'tipe' => $tipe, 'nilai' => $nilai];
    }
}
