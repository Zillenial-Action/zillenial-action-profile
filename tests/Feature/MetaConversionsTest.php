<?php

namespace Tests\Feature;

use App\Jobs\SendMetaConversionEvent;
use App\Mail\SendTicket;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\MetaTrackingData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaConversionsTest extends TestCase
{
    use RefreshDatabase;

    private const UA = 'Mozilla/5.0 (Test Browser)';

    private const FBP = 'fb.1.1732400000000.1234567890';

    private const FBC = 'fb.1.1732400000000.IwAR0abc_DEF-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.pixel_id' => '111222333',
            'services.meta.access_token' => 'secret-token',
            'services.meta.graph_version' => 'v26.0',
            'services.meta.test_event_code' => null,
            'services.customer_auth.frontend_url' => 'https://sostrip.test',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        Mail::fake();

        $this->app->instance(MidtransService::class, new class extends MidtransService
        {
            public function __construct() {}

            public function createSnapToken(Transaksi $transaksi): string
            {
                return 'fake-snap-token';
            }
        });
    }

    private function disableMeta(): void
    {
        config(['services.meta.access_token' => null]);
    }

    /** @return array<int, array<string, mixed>> */
    private function sentEvents(): array
    {
        return Http::recorded()
            ->map(fn (array $pair) => $pair[0]->data()['data'][0])
            ->values()
            ->all();
    }

    private function event(): Event
    {
        return Event::factory()->create([
            'slug' => 'social-trip',
            'name' => 'Social Trip',
            'status' => true,
            'jumlah_tiket' => 10,
            'harga' => 100000,
            'ada_baju' => false,
        ]);
    }

    private function checkout(array $extra = []): Transaksi
    {
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => null,
        ]);

        $response = $this->withHeader('User-Agent', self::UA)->postJson('/api/checkout', $extra + [
            'event_slug' => 'social-trip',
            'jumlah_tiket' => 1,
            'payment_method_id' => $payment->id,
            'pengunjung' => [
                ['name' => 'Budi Santoso', 'telepon' => '081234567890', 'email' => 'Budi@Gmail.com', 'domisili' => 'Bandung'],
            ],
        ])->assertOk();

        return Transaksi::where('invoice', $response->json('order_id'))->firstOrFail();
    }

    private function pendingTransaksi(array $attributes = []): Transaksi
    {
        return Transaksi::factory()->create($attributes + [
            'id_event' => $this->event()->id,
            'jumlah_tiket' => 2,
            'total_pembayaran' => 200000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_pembayaran' => null,
            'pengunjung_data' => [
                ['name' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
            'meta_tracking' => [
                'fbp' => self::FBP,
                'fbc' => self::FBC,
                'ip' => '203.0.113.10',
                'user_agent' => self::UA,
            ],
        ]);
    }

    private function settle(Transaksi $transaksi): \Illuminate\Testing\TestResponse
    {
        config(['midtrans.server_key' => 'server-test-key']);
        $gross = $transaksi->total_pembayaran.'.00';

        return $this->postJson('/midtrans/notification', [
            'order_id' => $transaksi->invoice,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => $gross,
            'signature_key' => hash('sha512', $transaksi->invoice.'200'.$gross.'server-test-key'),
            'fraud_status' => 'accept',
        ]);
    }

    public function test_tracking_data_keeps_valid_ids_and_drops_invalid_ones(): void
    {
        $request = Request::create('/', 'POST', server: ['HTTP_USER_AGENT' => self::UA, 'REMOTE_ADDR' => '203.0.113.10']);

        $valid = MetaTrackingData::capture($request, [
            'fbp' => self::FBP,
            'fbc' => self::FBC,
            'source_url' => 'https://sostrip.test/events/detail?slug=social-trip',
        ]);

        $this->assertSame([
            'fbp' => self::FBP,
            'fbc' => self::FBC,
            'source_url' => 'https://sostrip.test/events/detail?slug=social-trip',
            'ip' => '203.0.113.10',
            'user_agent' => self::UA,
        ], $valid);

        $invalid = MetaTrackingData::capture($request, [
            'fbp' => 'fb.1.abc.<script>',
            'fbc' => ['array'],
            'source_url' => 'https://evil.example/phish',
        ]);

        $this->assertSame(['ip' => '203.0.113.10', 'user_agent' => self::UA], $invalid);
        $this->assertSame(['ip' => '203.0.113.10', 'user_agent' => self::UA], MetaTrackingData::capture($request, 'bukan-array'));
    }

    public function test_checkout_stores_snapshot_and_sends_initiate_checkout(): void
    {
        $this->event();

        $transaksi = $this->checkout(['meta' => ['fbp' => self::FBP, 'fbc' => self::FBC]]);

        $this->assertSame(self::FBP, $transaksi->meta_tracking['fbp']);
        $this->assertSame(self::FBC, $transaksi->meta_tracking['fbc']);
        $this->assertSame(self::UA, $transaksi->meta_tracking['user_agent']);
        $this->assertNotEmpty($transaksi->meta_tracking['ip']);

        $events = $this->sentEvents();
        $this->assertCount(1, $events);
        $this->assertSame('InitiateCheckout', $events[0]['event_name']);
        $this->assertSame('checkout-'.$transaksi->invoice, $events[0]['event_id']);
        $this->assertSame('website', $events[0]['action_source']);
        $this->assertSame('https://sostrip.test/payment?order_id='.$transaksi->invoice, $events[0]['event_source_url']);
        $this->assertSame(100000, $events[0]['custom_data']['value']);

        // Email di-lowercase dan telepon dinormalisasi ke 62... sebelum di-hash.
        $this->assertSame([hash('sha256', 'budi@gmail.com')], $events[0]['user_data']['em']);
        $this->assertSame([hash('sha256', '6281234567890')], $events[0]['user_data']['ph']);
        $this->assertSame([hash('sha256', 'budi')], $events[0]['user_data']['fn']);
        $this->assertSame(self::FBP, $events[0]['user_data']['fbp']);
        $this->assertSame(self::UA, $events[0]['user_data']['client_user_agent']);
    }

    public function test_token_is_sent_in_body_and_never_in_url(): void
    {
        $this->event();
        config(['services.meta.test_event_code' => 'TEST123']);

        $this->checkout();

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === 'https://graph.facebook.com/v26.0/111222333/events'
                && $request['access_token'] === 'secret-token'
                && $request['test_event_code'] === 'TEST123';
        });
    }

    public function test_checkout_without_meta_payload_still_succeeds(): void
    {
        $this->event();

        $transaksi = $this->checkout(['meta' => 'rusak']);

        $this->assertSame('Pending', $transaksi->status_pembayaran);
        $this->assertArrayNotHasKey('fbp', $transaksi->meta_tracking);
    }

    public function test_disabled_meta_stores_nothing_and_sends_nothing(): void
    {
        $this->disableMeta();
        $this->event();

        $transaksi = $this->checkout(['meta' => ['fbp' => self::FBP]]);

        $this->assertNull($transaksi->meta_tracking);
        Http::assertNothingSent();
    }

    public function test_tracking_snapshot_is_not_exposed_by_status_api(): void
    {
        $this->event();
        $transaksi = $this->checkout(['meta' => ['fbp' => self::FBP]]);

        $response = $this->getJson('/api/transaksi/'.$transaksi->invoice.'?token='.$transaksi->public_token);

        $this->assertStringNotContainsString(self::FBP, $response->getContent());
        $this->assertStringNotContainsString('meta_tracking', $response->getContent());
        $this->assertArrayNotHasKey('meta_tracking', $transaksi->toArray());
    }

    public function test_settlement_webhook_sends_purchase_once(): void
    {
        $transaksi = $this->pendingTransaksi();

        $this->settle($transaksi)->assertOk();
        $this->settle($transaksi)->assertOk();

        $events = $this->sentEvents();
        $this->assertCount(1, $events, 'Webhook ulangan tidak boleh mengirim Purchase lagi.');
        $this->assertSame('Purchase', $events[0]['event_name']);
        $this->assertSame('purchase-'.$transaksi->invoice, $events[0]['event_id']);
        $this->assertSame('website', $events[0]['action_source']);
        $this->assertSame(200000, $events[0]['custom_data']['value']);
        $this->assertSame('IDR', $events[0]['custom_data']['currency']);
        $this->assertSame(2, $events[0]['custom_data']['num_items']);
        $this->assertSame([(string) $transaksi->id_event], $events[0]['custom_data']['content_ids']);
        $this->assertSame('203.0.113.10', $events[0]['user_data']['client_ip_address']);
        $this->assertSame(self::FBC, $events[0]['user_data']['fbc']);

        $transaksi->refresh();
        $this->assertSame('Success', $transaksi->status_pembayaran);
        $this->assertNotNull($transaksi->meta_purchase_sent_at);
        // IP & user agent dibuang setelah Purchase terkirim.
        // assertEquals: MySQL tidak menjaga urutan kunci kolom JSON.
        $this->assertEquals(['fbp' => self::FBP, 'fbc' => self::FBC], $transaksi->meta_tracking);
    }

    public function test_meta_events_use_their_own_queue_and_ticket_emails_stay_on_default(): void
    {
        Queue::fake();
        $transaksi = $this->pendingTransaksi();

        $this->settle($transaksi)->assertOk();

        Queue::assertPushedOn('meta', SendMetaConversionEvent::class);
        Queue::assertPushed(SendMetaConversionEvent::class, 1);
        Mail::assertQueued(SendTicket::class, fn (SendTicket $mail) => $mail->queue === null);

        config(['services.meta.queue' => 'tracking']);
        $this->assertSame('tracking', (new SendMetaConversionEvent(['event_name' => 'PageView']))->queue);
    }

    public function test_pending_notification_does_not_send_purchase(): void
    {
        $transaksi = $this->pendingTransaksi();
        config(['midtrans.server_key' => 'server-test-key']);
        $gross = $transaksi->total_pembayaran.'.00';

        $this->postJson('/midtrans/notification', [
            'order_id' => $transaksi->invoice,
            'transaction_status' => 'pending',
            'status_code' => '201',
            'gross_amount' => $gross,
            'signature_key' => hash('sha512', $transaksi->invoice.'201'.$gross.'server-test-key'),
        ]);

        Http::assertNothingSent();
    }

    public function test_meta_failure_does_not_block_payment_success(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'down']], 500)]);

        $transaksi = $this->pendingTransaksi();

        $this->settle($transaksi)->assertOk();

        $transaksi->refresh();
        $this->assertSame('Success', $transaksi->status_pembayaran);
        $this->assertNull($transaksi->meta_purchase_sent_at);
        $this->assertSame(1, $transaksi->volunteers()->count());
    }

    public function test_admin_manual_success_sends_purchase_as_system_generated_without_browser_snapshot(): void
    {
        $this->actingAs(User::factory()->create());
        $transaksi = $this->pendingTransaksi(['meta_tracking' => null]);

        $this->postJson(route('transaksi.update-status'), ['id' => $transaksi->id, 'status' => 'Success'])->assertOk();

        $events = $this->sentEvents();
        $this->assertCount(1, $events);
        $this->assertSame('Purchase', $events[0]['event_name']);
        $this->assertSame('system_generated', $events[0]['action_source']);
        $this->assertArrayNotHasKey('client_user_agent', $events[0]['user_data']);
        $this->assertSame([hash('sha256', 'budi@gmail.com')], $events[0]['user_data']['em']);
    }

    public function test_page_view_endpoint_forwards_browser_event(): void
    {
        $this->withHeader('User-Agent', self::UA)->postJson('/api/meta/events', [
            'event_name' => 'PageView',
            'event_id' => 'pv-3f2a9c10-aaaa-bbbb',
            'meta' => ['fbp' => self::FBP, 'source_url' => 'https://sostrip.test/events'],
        ])->assertStatus(202)->assertJson(['sent' => true]);

        $events = $this->sentEvents();
        $this->assertCount(1, $events);
        $this->assertSame('PageView', $events[0]['event_name']);
        $this->assertSame('pv-3f2a9c10-aaaa-bbbb', $events[0]['event_id']);
        $this->assertSame('https://sostrip.test/events', $events[0]['event_source_url']);
        $this->assertSame(self::FBP, $events[0]['user_data']['fbp']);
        $this->assertSame(self::UA, $events[0]['user_data']['client_user_agent']);
        $this->assertArrayNotHasKey('custom_data', $events[0]);
    }

    public function test_view_content_takes_content_data_from_database_not_from_body(): void
    {
        $event = $this->event();

        $this->withHeader('User-Agent', self::UA)->postJson('/api/meta/events', [
            'event_name' => 'ViewContent',
            'event_id' => 'vc-3f2a9c10-aaaa-bbbb',
            'event_slug' => 'social-trip',
            'value' => 1,
            'content_name' => 'palsu',
        ])->assertStatus(202);

        $custom = $this->sentEvents()[0]['custom_data'];
        $this->assertSame([(string) $event->id], $custom['content_ids']);
        $this->assertSame('Social Trip', $custom['content_name']);
        $this->assertSame(100000, $custom['value']);

        $this->postJson('/api/meta/events', [
            'event_name' => 'AddToCart',
            'event_id' => 'atc-3f2a9c10-aaaa-bbbb',
            'event_slug' => 'tidak-ada',
        ])->assertStatus(422);
    }

    public function test_events_endpoint_rejects_unknown_event_names_and_bad_ids(): void
    {
        $this->postJson('/api/meta/events', ['event_name' => 'Purchase', 'event_id' => 'purchase-INV-1'])
            ->assertStatus(422);
        $this->postJson('/api/meta/events', ['event_name' => 'PageView', 'event_id' => 'pv <script>'])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_complete_registration_requires_customer_and_uses_account_event_id(): void
    {
        $payload = ['event_name' => 'CompleteRegistration', 'event_id' => 'register-dari-browser'];

        $this->withHeader('User-Agent', self::UA)->postJson('/api/meta/events', $payload)->assertStatus(401);
        Http::assertNothingSent();

        $customer = Customer::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@gmail.com']);
        $token = $customer->createToken('sostrip')->plainTextToken;

        $this->withHeader('User-Agent', self::UA)->withToken($token)
            ->postJson('/api/meta/events', $payload)->assertStatus(202);

        $event = $this->sentEvents()[0];
        $this->assertSame('CompleteRegistration', $event['event_name']);
        $this->assertSame('register-'.$customer->id, $event['event_id']);
        $this->assertSame([hash('sha256', 'siti@gmail.com')], $event['user_data']['em']);
        $this->assertSame([hash('sha256', (string) $customer->id)], $event['user_data']['external_id']);
    }

    public function test_events_endpoint_is_a_no_op_when_meta_is_disabled(): void
    {
        $this->disableMeta();

        $this->postJson('/api/meta/events', ['event_name' => 'PageView', 'event_id' => 'pv-3f2a9c10-aaaa'])
            ->assertOk()->assertJson(['sent' => false]);

        Http::assertNothingSent();
    }
}
