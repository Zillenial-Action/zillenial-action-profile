<?php

namespace App\Support;

/**
 * Normalisasi data UTM kiriman frontend sebelum disimpan di transaksi.
 * Data berasal dari browser, jadi hanya kunci utm_* yang valid yang diterima
 * dan panjang nilainya dibatasi. Data yang tidak valid dibuang diam-diam:
 * tracking tidak boleh membuat checkout gagal.
 */
class UtmData
{
    public const MAX_PARAMS = 20;

    public const MAX_VALUE_LENGTH = 191;

    public const MAX_URL_LENGTH = 500;

    private const KEY_PATTERN = '/^utm_[a-z0-9_]{1,40}$/';

    /**
     * Ubah payload `utm` menjadi atribut transaksi. Last touch dipakai untuk
     * kolom laporan; first touch tetap disimpan di utm_data.
     *
     * @return array{utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null, utm_data?: array<string, mixed>}
     */
    public static function toAttributes(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $first = self::touch($payload['first'] ?? null);
        $last = self::touch($payload['last'] ?? null) ?? $first;

        if (! $last) {
            return [];
        }

        return [
            'utm_source' => $last['params']['utm_source'] ?? null,
            'utm_medium' => $last['params']['utm_medium'] ?? null,
            'utm_campaign' => $last['params']['utm_campaign'] ?? null,
            'utm_data' => array_filter(['first' => $first, 'last' => $last]),
        ];
    }

    /**
     * @return array{params: array<string, string>, landing_page: string|null, referrer: string|null, captured_at: string|null}|null
     */
    private static function touch(mixed $touch): ?array
    {
        if (! is_array($touch) || ! is_array($touch['params'] ?? null)) {
            return null;
        }

        $params = [];
        foreach ($touch['params'] as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $key = strtolower((string) $key);
            $value = trim((string) $value);
            if ($value === '' || ! preg_match(self::KEY_PATTERN, $key)) {
                continue;
            }
            $params[$key] = mb_substr($value, 0, self::MAX_VALUE_LENGTH);
            if (count($params) >= self::MAX_PARAMS) {
                break;
            }
        }

        if ($params === []) {
            return null;
        }

        return [
            'params' => $params,
            'landing_page' => self::text($touch['landing_page'] ?? null),
            'referrer' => self::text($touch['referrer'] ?? null),
            'captured_at' => self::timestamp($touch['captured_at'] ?? null),
        ];
    }

    private static function timestamp(mixed $value): ?string
    {
        $time = is_string($value) ? strtotime($value) : false;

        return $time === false ? null : date(DATE_ATOM, $time);
    }

    private static function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : mb_substr($value, 0, self::MAX_URL_LENGTH);
    }
}
