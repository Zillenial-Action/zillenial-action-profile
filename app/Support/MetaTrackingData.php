<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Normalisasi data pencocokan Meta (fbp, fbc, URL halaman) kiriman frontend, ditambah
 * IP dan user agent yang dibaca server dari request. Data dari browser tidak dipercaya:
 * yang formatnya salah dibuang diam-diam, karena tracking tidak boleh membuat request gagal.
 */
class MetaTrackingData
{
    public const MAX_FBP_LENGTH = 100;

    public const MAX_FBC_LENGTH = 500;

    public const MAX_URL_LENGTH = 500;

    public const MAX_USER_AGENT_LENGTH = 500;

    private const FBP_PATTERN = '/^fb\.\d\.\d{10,16}\.\d{1,30}$/';

    private const FBC_PATTERN = '/^fb\.\d\.\d{10,16}\.[A-Za-z0-9_-]{1,450}$/';

    /**
     * @return array{fbp?: string, fbc?: string, source_url?: string, ip?: string, user_agent?: string}
     */
    public static function capture(Request $request, mixed $payload): array
    {
        $payload = is_array($payload) ? $payload : [];

        return array_filter([
            'fbp' => self::match($payload['fbp'] ?? null, self::FBP_PATTERN, self::MAX_FBP_LENGTH),
            'fbc' => self::match($payload['fbc'] ?? null, self::FBC_PATTERN, self::MAX_FBC_LENGTH),
            'source_url' => self::sourceUrl($payload['source_url'] ?? null),
            'ip' => $request->ip(),
            'user_agent' => self::userAgent($request->userAgent()),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function match(mixed $value, string $pattern, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return strlen($value) <= $max && preg_match($pattern, $value) ? $value : null;
    }

    /**
     * Hanya URL milik frontend Sostrip yang diterima, supaya event tidak bisa diberi
     * alamat halaman situs lain.
     */
    private static function sourceUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > self::MAX_URL_LENGTH || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $url = parse_url($value);
        $frontend = parse_url((string) config('services.customer_auth.frontend_url'));

        if (! in_array($url['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }

        $sameHost = strtolower($url['host'] ?? '') === strtolower($frontend['host'] ?? '')
            && ($url['port'] ?? null) === ($frontend['port'] ?? null);

        return $sameHost ? $value : null;
    }

    private static function userAgent(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, self::MAX_USER_AGENT_LENGTH);
    }
}
