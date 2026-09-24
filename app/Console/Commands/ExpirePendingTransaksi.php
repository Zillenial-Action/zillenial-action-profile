<?php

namespace App\Console\Commands;

use App\Models\Transaksi;
use App\Services\CheckoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fallback jika webhook 'expire' dari Midtrans tidak pernah sampai:
 * tandai transaksi Pending yang sudah lewat batas waktu sebagai Failed
 * dan kembalikan stok tiket serta kuota voucher yang direservasi.
 *
 * Batas waktu disinkronkan dengan expiry asli Midtrans (payment_instructions.expiry_time)
 * yang juga dipakai timer di frontend — ditambah grace period agar tidak pernah melepas
 * stok saat pembayaran masih mungkin berhasil. Bila expiry_time tidak tersedia, dipakai
 * TTL flat yang lebih panjang dari masa berlaku maksimum Midtrans (default VA 24 jam).
 */
class ExpirePendingTransaksi extends Command
{
    protected $signature = 'transaksi:expire-pending
        {--hours= : TTL fallback (jam) bila expiry_time tidak tersedia}
        {--grace= : Grace period (menit) setelah expiry_time sebelum stok dilepas}';

    protected $description = 'Expire transaksi Pending yang sudah lewat batas bayar dan kembalikan stok tiket serta kuota voucher.';

    public function handle(CheckoutService $checkoutService): int
    {
        $fallbackHours = (int) ($this->option('hours') ?: config('midtrans.pending_ttl_hours', 25));
        $graceMinutes  = (int) ($this->option('grace') ?: config('midtrans.expiry_grace_minutes', 15));

        $now   = now();
        $count = 0;

        Transaksi::where('status_pembayaran', 'Pending')
            ->orderBy('id')
            ->chunkById(200, function ($transaksis) use ($checkoutService, $now, $fallbackHours, $graceMinutes, &$count) {
                foreach ($transaksis as $transaksi) {
                    $deadline = $this->resolveDeadline($transaksi, $fallbackHours, $graceMinutes);

                    // Belum lewat batas bayar → jangan sentuh (pembayaran masih mungkin berhasil).
                    if ($now->lessThan($deadline)) {
                        continue;
                    }

                    DB::transaction(function () use ($checkoutService, $transaksi, &$count) {
                        // Kunci baris & pastikan masih Pending untuk menghindari balapan dengan webhook.
                        $fresh = Transaksi::whereKey($transaksi->id)
                            ->where('status_pembayaran', 'Pending')
                            ->lockForUpdate()
                            ->first();

                        if (! $fresh) {
                            return;
                        }

                        $checkoutService->releaseReservation($fresh);
                        $fresh->update(['status_pembayaran' => 'Failed']);
                        $count++;
                    });
                }
            });

        Log::info('Expire pending transaksi selesai', [
            'expired'        => $count,
            'fallback_hours' => $fallbackHours,
            'grace_minutes'  => $graceMinutes,
        ]);

        $this->info("Selesai. {$count} transaksi Pending di-expire.");

        return self::SUCCESS;
    }

    /**
     * Batas waktu efektif sebelum stok boleh dilepas.
     * Prioritaskan expiry_time asli Midtrans (+ grace) agar sinkron dengan timer frontend.
     */
    private function resolveDeadline(Transaksi $transaksi, int $fallbackHours, int $graceMinutes): Carbon
    {
        $expiry = Arr::get((array) $transaksi->payment_instructions, 'expiry_time');

        if (! empty($expiry)) {
            try {
                // expiry_time Midtrans dalam zona waktu aplikasi (Asia/Jakarta), tanpa offset.
                return Carbon::parse($expiry)->addMinutes($graceMinutes);
            } catch (\Throwable $e) {
                Log::warning('Gagal parse expiry_time, pakai TTL fallback', [
                    'invoice' => $transaksi->invoice,
                    'expiry'  => $expiry,
                ]);
            }
        }

        $base = $transaksi->created_at ?? $transaksi->tanggal_register ?? now();

        return Carbon::parse($base)->addHours($fallbackHours);
    }
}
