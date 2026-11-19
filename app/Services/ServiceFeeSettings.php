<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Biaya layanan checkout tiket, diatur dari admin (menu Biaya Layanan).
 *
 * - percent: persen dari subtotal tiket setelah voucher, dibulatkan ke atas ke rupiah.
 * - fixed:   nominal rupiah per tiket.
 * - none:    tanpa biaya layanan.
 *
 * Rumus yang sama dipakai frontend untuk preview (zillenial-action-sostrip checkout/detail.astro);
 * ubah keduanya bersamaan.
 */
class ServiceFeeSettings
{
    public const TYPE_NONE = 'none';

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [self::TYPE_NONE, self::TYPE_PERCENT, self::TYPE_FIXED];

    private const CACHE_KEY = 'settings.service_fee';

    private const DEFAULTS = [
        'service_fee_type' => self::TYPE_NONE,
        'service_fee_value' => '0',
    ];

    /**
     * @return array{tipe: string, nilai: float}
     */
    public function config(): array
    {
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
                ->whereIn('key', array_keys(self::DEFAULTS))
                ->pluck('value', 'key')
                ->all());
        } catch (\Throwable $e) {
            // Mis. tabel settings belum dimigrasi: checkout tetap jalan tanpa biaya layanan.
            Log::warning('Service fee settings unavailable, using defaults', ['error' => $e->getMessage()]);
            $stored = [];
        }

        $values = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));
        $tipe = in_array($values['service_fee_type'], self::TYPES, true) ? $values['service_fee_type'] : self::TYPE_NONE;
        $nilai = max(0, (float) $values['service_fee_value']);

        if ($tipe === self::TYPE_NONE || $nilai <= 0) {
            return ['tipe' => self::TYPE_NONE, 'nilai' => 0.0];
        }

        return ['tipe' => $tipe, 'nilai' => $nilai];
    }

    /**
     * Hitung biaya layanan untuk satu checkout.
     *
     * @return array{amount: int, tipe: ?string, nilai: ?float}
     */
    public function calculate(int $subtotal, int $jumlahTiket): array
    {
        ['tipe' => $tipe, 'nilai' => $nilai] = $this->config();

        // Event gratis / voucher 100%: tidak ada biaya layanan.
        if ($tipe === self::TYPE_NONE || $subtotal <= 0 || $jumlahTiket <= 0) {
            return ['amount' => 0, 'tipe' => null, 'nilai' => null];
        }

        $amount = match ($tipe) {
            // round() dulu supaya galat float (mis. 100000 * 1.1 / 100) tidak menambah Rp1.
            self::TYPE_PERCENT => (int) ceil(round($subtotal * $nilai / 100, 6)),
            self::TYPE_FIXED => (int) round($nilai) * $jumlahTiket,
        };

        return ['amount' => $amount, 'tipe' => $tipe, 'nilai' => $nilai];
    }

    public function update(string $tipe, float $nilai): void
    {
        $values = [
            'service_fee_type' => $tipe,
            'service_fee_value' => $tipe === self::TYPE_NONE ? '0' : (string) $nilai,
        ];

        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
