<?php

namespace App\Jobs;

use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim satu event ke Meta Conversions API.
 *
 * Token dibaca dari config saat job berjalan, jadi tidak ikut tersimpan di tabel jobs.
 *
 * Job masuk antrean `services.meta.queue` (default "meta"). Worker harus memprosesnya,
 * dengan antrean default lebih dulu: `php artisan queue:work --queue=default,meta`.
 */
class SendMetaConversionEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600, 1800];

    /**
     * @param  array<string, mixed>  $event  Satu elemen `data` Conversions API.
     * @param  int|null  $purchaseTransaksiId  Diisi untuk event Purchase: transaksi ditandai terkirim.
     */
    public function __construct(public array $event, public ?int $purchaseTransaksiId = null)
    {
        // Antrean sendiri: lonjakan PageView tidak boleh menunda email tiket di antrean default.
        $this->onQueue((string) config('services.meta.queue', 'meta'));
    }

    public function handle(): void
    {
        $pixelId = (string) config('services.meta.pixel_id');
        $token = (string) config('services.meta.access_token');

        if ($pixelId === '' || $token === '') {
            return;
        }

        $version = (string) config('services.meta.graph_version', 'v26.0');

        // access_token dikirim di body, bukan di URL, agar tidak muncul di log request.
        $body = ['data' => [$this->event], 'access_token' => $token];
        if (filled(config('services.meta.test_event_code'))) {
            $body['test_event_code'] = (string) config('services.meta.test_event_code');
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->asJson()
            ->post("https://graph.facebook.com/{$version}/{$pixelId}/events", $body);

        if ($response->successful()) {
            $this->markPurchaseSent();

            return;
        }

        $context = [
            'event' => $this->event['event_name'] ?? null,
            'event_id' => $this->event['event_id'] ?? null,
            'status' => $response->status(),
            'error' => $response->json('error.message'),
            'error_code' => $response->json('error.code'),
        ];

        // 4xx (selain 429) berarti payload/token salah: mengulang tidak akan membantu.
        if ($response->clientError() && $response->status() !== 429) {
            Log::error('Meta CAPI: event ditolak', $context);
            $this->fail(new \RuntimeException('Meta CAPI menolak event (HTTP '.$response->status().').'));

            return;
        }

        Log::warning('Meta CAPI: gagal sementara, akan dicoba lagi', $context);

        throw new \RuntimeException('Meta CAPI gagal sementara (HTTP '.$response->status().').');
    }

    /**
     * Tandai Purchase terkirim dan buang IP/user agent yang sudah tidak diperlukan.
     */
    private function markPurchaseSent(): void
    {
        if ($this->purchaseTransaksiId === null) {
            return;
        }

        $transaksi = Transaksi::withTrashed()->find($this->purchaseTransaksiId);
        if (! $transaksi) {
            return;
        }

        $tracking = is_array($transaksi->meta_tracking) ? $transaksi->meta_tracking : [];
        unset($tracking['ip'], $tracking['user_agent']);

        $transaksi->forceFill([
            'meta_purchase_sent_at' => now(),
            'meta_tracking' => $tracking === [] ? null : $tracking,
        ])->save();
    }
}
