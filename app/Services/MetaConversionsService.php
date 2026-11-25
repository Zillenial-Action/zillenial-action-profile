<?php

namespace App\Services;

use App\Jobs\SendMetaConversionEvent;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Transaksi;
use App\Support\MetaTrackingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Meta Conversions API: menyusun event server-ke-server dan mengantrikannya.
 *
 * Pixel browser di Sostrip tetap jalan. Event di sini memakai event_name dan event_id
 * yang sama dengan browser (lihat src/lib/pixel.ts di Sostrip) supaya Meta
 * menghitungnya sekali. Kegagalan di sini tidak boleh mengganggu checkout atau pembayaran.
 */
class MetaConversionsService
{
    /** Event yang boleh dikirim browser lewat POST /api/meta/events. */
    public const BROWSER_EVENTS = ['PageView', 'ViewContent', 'AddToCart', 'CompleteRegistration'];

    public function enabled(): bool
    {
        return filled(config('services.meta.pixel_id')) && filled(config('services.meta.access_token'));
    }

    /**
     * Atribut transaksi berisi snapshot pencocokan Meta, untuk digabung dengan atribut UTM
     * saat checkout. Kosong bila fitur mati, supaya IP/user agent tidak disimpan tanpa guna.
     *
     * @return array{meta_tracking?: array<string, string>}
     */
    public function trackingAttributes(Request $request): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $tracking = MetaTrackingData::capture($request, $request->input('meta'));

        return $tracking === [] ? [] : ['meta_tracking' => $tracking];
    }

    public static function checkoutEventId(string $invoice): string
    {
        return 'checkout-'.$invoice;
    }

    public static function purchaseEventId(string $invoice): string
    {
        return 'purchase-'.$invoice;
    }

    public static function registrationEventId(int $customerId): string
    {
        return 'register-'.$customerId;
    }

    public function initiateCheckout(Transaksi $transaksi): void
    {
        $this->transaksiEvent(
            $transaksi,
            'InitiateCheckout',
            self::checkoutEventId($transaksi->invoice),
            $transaksi->tanggal_register?->getTimestamp() ?? time(),
            $this->frontendUrl('/payment?order_id='.rawurlencode($transaksi->invoice))
        );
    }

    /**
     * Dipanggil setiap status berubah menjadi Success (webhook Midtrans maupun admin).
     */
    public function purchase(Transaksi $transaksi): void
    {
        if ($transaksi->meta_purchase_sent_at !== null) {
            return;
        }

        $this->transaksiEvent(
            $transaksi,
            'Purchase',
            self::purchaseEventId($transaksi->invoice),
            $transaksi->tanggal_pembayaran?->getTimestamp() ?? time(),
            $this->frontendUrl('/payment/success?order_id='.rawurlencode($transaksi->invoice)),
            $transaksi->id
        );
    }

    /**
     * Event yang berasal dari halaman Sostrip (PageView, ViewContent, AddToCart,
     * CompleteRegistration). Browser mengirim event yang sama ke pixel dengan event_id ini.
     *
     * @param  array<string, string>  $tracking  Hasil MetaTrackingData::capture().
     */
    public function browserEvent(string $name, string $eventId, array $tracking, ?Event $event = null, ?Customer $customer = null): void
    {
        if (! $this->enabled() || empty($tracking['user_agent'])) {
            return;
        }

        $userData = $this->matchData($tracking);
        if ($customer) {
            $userData += $this->hashedIdentity($customer->email, null, $customer->name, (string) $customer->id);
        }

        $this->dispatch([
            'event_name' => $name,
            'event_time' => time(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $tracking['source_url'] ?? $this->frontendUrl('/'),
            'user_data' => $userData,
        ] + ($event ? ['custom_data' => $this->contentData($event, (int) $event->harga)] : []));
    }

    private function transaksiEvent(Transaksi $transaksi, string $name, string $eventId, int $time, string $sourceUrl, ?int $markPurchaseId = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $tracking = is_array($transaksi->meta_tracking) ? $transaksi->meta_tracking : [];
            $transaksi->loadMissing('event');

            $custom = [
                'currency' => 'IDR',
                'value' => (int) $transaksi->total_pembayaran,
                'num_items' => (int) $transaksi->jumlah_tiket,
                'order_id' => $transaksi->invoice,
            ];
            if ($transaksi->event) {
                $custom = $this->contentData($transaksi->event, (int) $transaksi->total_pembayaran) + $custom;
            }

            $this->dispatch([
                'event_name' => $name,
                'event_time' => $time,
                'event_id' => $eventId,
                // Meta mewajibkan user agent untuk event "website". Transaksi tanpa snapshot
                // browser (input manual admin, transaksi lama) dikirim sebagai system_generated.
                'action_source' => empty($tracking['user_agent']) ? 'system_generated' : 'website',
                'event_source_url' => $sourceUrl,
                'user_data' => $this->matchData($tracking) + $this->hashedIdentity(
                    $transaksi->email,
                    $transaksi->telepon,
                    $transaksi->name,
                    $transaksi->id_customer ? (string) $transaksi->id_customer : null
                ),
                'custom_data' => $custom,
            ], $markPurchaseId);
        } catch (\Throwable $e) {
            Log::error('Meta CAPI: gagal menyusun event', [
                'event' => $name,
                'invoice' => $transaksi->invoice,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sama dengan commerceParams() di Sostrip (src/lib/pixel.ts).
     *
     * @return array<string, mixed>
     */
    private function contentData(Event $event, int $value): array
    {
        return [
            'content_ids' => [(string) $event->id],
            'content_name' => $event->name,
            'content_type' => 'product',
            'value' => $value,
            'currency' => 'IDR',
        ];
    }

    /**
     * Parameter yang dikirim apa adanya (tidak di-hash) sesuai aturan Meta.
     *
     * @param  array<string, string>  $tracking
     * @return array<string, string>
     */
    private function matchData(array $tracking): array
    {
        return array_filter([
            'fbp' => $tracking['fbp'] ?? null,
            'fbc' => $tracking['fbc'] ?? null,
            'client_ip_address' => $tracking['ip'] ?? null,
            'client_user_agent' => $tracking['user_agent'] ?? null,
        ]);
    }

    /**
     * Data pribadi dinormalisasi lalu di-hash SHA-256 sebelum meninggalkan server.
     *
     * @return array<string, array<int, string>>
     */
    private function hashedIdentity(?string $email, ?string $phone, ?string $name, ?string $externalId): array
    {
        $email = strtolower(trim((string) $email));
        // Meta meminta digit saja dengan kode negara; transaksis.telepon tersimpan "+62...".
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }
        $firstName = mb_strtolower((string) (preg_split('/\s+/', trim((string) $name))[0] ?? ''));

        $values = [
            'em' => $email,
            'ph' => $phone,
            'fn' => $firstName,
            'country' => 'id',
            'external_id' => (string) $externalId,
        ];

        return array_map(
            fn (string $value) => [hash('sha256', $value)],
            array_filter($values, fn (string $value) => $value !== '')
        );
    }

    private function frontendUrl(string $path): string
    {
        return rtrim((string) config('services.customer_auth.frontend_url'), '/').$path;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function dispatch(array $event, ?int $markPurchaseId = null): void
    {
        try {
            SendMetaConversionEvent::dispatch($event, $markPurchaseId);
        } catch (\Throwable $e) {
            // Queue sync (test/dev) melempar error job langsung ke sini.
            Log::error('Meta CAPI: gagal mengirim/mengantrikan event', [
                'event' => $event['event_name'] ?? null,
                'event_id' => $event['event_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
