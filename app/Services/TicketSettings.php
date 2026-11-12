<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Isi tiket & email tiket yang bisa diatur dari admin (menu Pengaturan Tiket).
 *
 * Nilai default sama dengan teks yang sebelumnya di-hardcode di view, sehingga tiket
 * tetap tampil sama sampai admin mengubahnya.
 */
class TicketSettings
{
    private const CACHE_KEY = 'settings.ticket';

    public const DEFAULTS = [
        'ticket_terms' => '<ul>'
            . '<li>Mengisi formulir pendaftaran</li>'
            . '<li>Memberikan kontribusi sesuai yang tertera (sudah termasuk bantuan untuk penerima manfaat)</li>'
            . '<li>Tiket yang sudah dibeli tidak dapat dibatalkan</li>'
            . '<li>Bersedia mengikuti aturan yang berlaku selama kegiatan Zillenial Action berlangsung</li>'
            . '<li>Menjadi inspirasi kebaikan dengan membagikan pengalaman menjadi Sobat Zigi lewat media sosial yang kamu miliki dan di kolaborasikan dengan akun instagram @zillenialaction</li>'
            . '</ul>',
        'ticket_email_message' => '<p>Halo,</p>'
            . '<p>Terima kasih! Pembayaran Anda telah berhasil kami terima. Tiket Anda sudah siap.</p>',
        'ticket_contact_whatsapp' => '6282121392363',
        'ticket_contact_email' => 'zillenialaction@gmail.com',
        'ticket_contact_website' => 'https://zillenialaction.id',
    ];

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
                ->whereIn('key', array_keys(self::DEFAULTS))
                ->pluck('value', 'key')
                ->all());
        } catch (\Throwable $e) {
            // Mis. tabel settings belum dimigrasi: tiket tetap tampil dengan default.
            Log::warning('Ticket settings unavailable, using defaults', ['error' => $e->getMessage()]);
            $stored = [];
        }

        return array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @param array<string, string|null> $values
     */
    public function update(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Kolom event yang menimpa pengaturan default (kosong = pakai default).
     */
    private const EVENT_OVERRIDES = [
        'ticket_terms' => 'syarat_tiket',
        'ticket_email_message' => 'pesan_email_tiket',
        'ticket_contact_whatsapp' => 'kontak_whatsapp_tiket',
        'ticket_contact_email' => 'kontak_email_tiket',
        'ticket_contact_website' => 'kontak_website_tiket',
    ];

    /**
     * Nilai untuk event tertentu: milik event bila diisi, selain itu default.
     */
    public function getFor(string $key, ?Event $event): ?string
    {
        $column = self::EVENT_OVERRIDES[$key] ?? null;
        $eventValue = $column ? $event?->{$column} : null;

        return $this->isBlank($eventValue) ? $this->get($key) : $eventValue;
    }

    /**
     * S&K yang tampil di tiket: milik event bila diisi, selain itu default.
     */
    public function termsFor(?Event $event): ?string
    {
        return $this->getFor('ticket_terms', $event);
    }

    /**
     * Pembuka email tiket: milik event bila diisi, selain itu default.
     */
    public function emailMessageFor(?Event $event): ?string
    {
        return $this->getFor('ticket_email_message', $event);
    }

    /**
     * Link wa.me dari nomor yang disimpan (08xx / +62xx / 62xx).
     */
    public function whatsappLink(?Event $event = null): ?string
    {
        $digits = $this->whatsappDigits($event);

        return $digits ? 'https://wa.me/' . $digits : null;
    }

    public function whatsappLabel(?Event $event = null): ?string
    {
        $digits = $this->whatsappDigits($event);

        return $digits ? '+' . $digits : null;
    }

    public function contactEmail(?Event $event = null): ?string
    {
        return $this->getFor('ticket_contact_email', $event);
    }

    public function website(?Event $event = null): ?string
    {
        return $this->getFor('ticket_contact_website', $event);
    }

    public function websiteLabel(?Event $event = null): ?string
    {
        $website = $this->website($event);

        return $website ? preg_replace('#^https?://#i', '', rtrim($website, '/')) : null;
    }

    private function whatsappDigits(?Event $event): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->getFor('ticket_contact_whatsapp', $event));
        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
    }

    private function isBlank(?string $html): bool
    {
        return trim(strip_tags((string) $html)) === '';
    }
}
