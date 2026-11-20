<?php

namespace Tests\Feature;

use App\Http\Resources\TransaksiStatusResource;
use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MidtransService;
use App\Services\ServiceFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ServiceFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(MidtransService::class, new class extends MidtransService
        {
            public function __construct() {}

            public function createSnapToken(Transaksi $transaksi): string
            {
                return 'fake-snap-token';
            }
        });
    }

    private function payment(string $tipe = 'none', float $nilai = 0): Payment
    {
        return Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => null,
            'biaya_layanan_tipe' => $tipe,
            'biaya_layanan_nilai' => $nilai,
        ]);
    }

    private function fee(Payment $payment, int $subtotal, int $qty): array
    {
        return app(ServiceFeeCalculator::class)->calculate($payment, $subtotal, $qty);
    }

    private function checkout(Payment $payment, int $qty, ?string $voucher = null): Transaksi
    {
        $pengunjung = [];
        for ($i = 1; $i <= $qty; $i++) {
            $pengunjung[] = ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => "budi{$i}@gmail.com", 'domisili' => 'Bandung'];
        }

        $response = $this->postJson('/api/checkout', [
            'event_slug' => 'social-trip',
            'jumlah_tiket' => $qty,
            'payment_method_id' => $payment->id,
            'voucher_code' => $voucher,
            'pengunjung' => $pengunjung,
        ])->assertOk();

        return Transaksi::where('invoice', $response->json('order_id'))->firstOrFail();
    }

    private function event(int $harga = 100000): Event
    {
        return Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
            'harga' => $harga,
            'ada_baju' => false,
        ]);
    }

    public function test_payment_without_fee_config_has_no_fee(): void
    {
        $payment = $this->payment();

        $this->assertSame(['tipe' => 'none', 'nilai' => 0.0], $payment->serviceFeeConfig());
        $this->assertSame(0, $this->fee($payment, 100000, 1)['amount']);
    }

    public function test_percent_is_based_on_subtotal_and_rounded_up(): void
    {
        $this->assertSame(2500, $this->fee($this->payment('percent', 2.5), 100000, 1)['amount']);
        // 99.999 x 2,5% = 2.499,975 -> dibulatkan ke atas
        $this->assertSame(2500, $this->fee($this->payment('percent', 2.5), 99999, 1)['amount']);
        // galat float tidak boleh menambah Rp1: 100.000 x 1,1% = 1.100
        $this->assertSame(1100, $this->fee($this->payment('percent', 1.1), 100000, 1)['amount']);
    }

    public function test_fixed_is_charged_per_ticket(): void
    {
        $result = $this->fee($this->payment('fixed', 5000), 300000, 3);

        $this->assertSame(['amount' => 15000, 'tipe' => 'fixed', 'nilai' => 5000.0], $result);
    }

    public function test_free_subtotal_has_no_service_fee(): void
    {
        $this->assertSame(['amount' => 0, 'tipe' => null, 'nilai' => null], $this->fee($this->payment('fixed', 5000), 0, 2));
    }

    public function test_each_payment_method_charges_its_own_fee(): void
    {
        $this->event(100000);
        $va = $this->payment('fixed', 4000);
        $qris = $this->payment('percent', 0.7);
        $gopay = $this->payment();

        $this->assertSame(208000, $this->checkout($va, 2)->total_pembayaran);
        $this->assertSame(201400, $this->checkout($qris, 2)->total_pembayaran);
        $this->assertSame(200000, $this->checkout($gopay, 2)->total_pembayaran);
    }

    public function test_checkout_adds_percent_fee_after_voucher_discount(): void
    {
        $event = $this->event(100000);
        KodeVoucher::factory()->create(['id_event' => $event->id, 'kode' => 'HEMAT', 'nilai_diskon' => 20000]);

        $transaksi = $this->checkout($this->payment('percent', 5), 2, 'HEMAT');

        $this->assertSame(160000, $transaksi->subtotal);
        $this->assertSame(8000, $transaksi->biaya_layanan);
        $this->assertSame(168000, $transaksi->total_pembayaran);
        $this->assertSame('percent', $transaksi->biaya_layanan_tipe);
        $this->assertSame(5.0, $transaksi->biaya_layanan_nilai);
    }

    public function test_checkout_without_fee_keeps_previous_total(): void
    {
        $this->event(150000);

        $transaksi = $this->checkout($this->payment(), 2);

        $this->assertSame(300000, $transaksi->subtotal);
        $this->assertSame(0, $transaksi->biaya_layanan);
        $this->assertNull($transaksi->biaya_layanan_tipe);
        $this->assertSame(300000, $transaksi->total_pembayaran);
    }

    public function test_changing_payment_fee_does_not_change_existing_transaction(): void
    {
        $this->event(100000);
        $payment = $this->payment('fixed', 5000);
        $transaksi = $this->checkout($payment, 1);

        $payment->update(['biaya_layanan_tipe' => 'percent', 'biaya_layanan_nilai' => 10]);

        $transaksi->refresh();
        $this->assertSame(5000, $transaksi->biaya_layanan);
        $this->assertSame(105000, $transaksi->total_pembayaran);
        $this->assertSame('fixed', $transaksi->biaya_layanan_tipe);
    }

    public function test_manual_transaction_defaults_subtotal_to_total(): void
    {
        $transaksi = Transaksi::factory()->create(['total_pembayaran' => 250000]);

        $this->assertSame(250000, $transaksi->fresh()->subtotal);
        $this->assertSame(0, $transaksi->fresh()->biaya_layanan);
    }

    public function test_snap_item_details_sum_to_gross_amount(): void
    {
        $transaksi = Transaksi::factory()->make([
            'jumlah_tiket' => 2,
            'subtotal' => 160000,
            'biaya_layanan' => 8000,
            'total_pembayaran' => 168000,
        ]);

        $items = (fn () => $this->itemDetails($transaksi))->call(new MidtransService);

        $this->assertCount(2, $items);
        $this->assertSame(160000, $items[0]['price']);
        $this->assertSame(['id' => 'BIAYA-LAYANAN', 'price' => 8000, 'quantity' => 1, 'name' => 'Biaya Layanan'], $items[1]);
        $this->assertSame(168000, array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items)));
    }

    public function test_snap_item_details_without_fee_is_single_item(): void
    {
        $transaksi = Transaksi::factory()->make(['total_pembayaran' => 150000, 'biaya_layanan' => 0]);

        $items = (fn () => $this->itemDetails($transaksi))->call(new MidtransService);

        $this->assertCount(1, $items);
        $this->assertSame(150000, $items[0]['price']);
    }

    public function test_payment_methods_api_exposes_fee_per_method(): void
    {
        Cache::forget('active_payment_methods');
        $va = $this->payment('fixed', 4000);
        $qris = $this->payment('percent', 0.7);

        $data = collect($this->getJson('/api/payment-methods')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame(['tipe' => 'fixed', 'nilai' => 4000], $data[$va->id]['biaya_layanan']);
        $this->assertSame(['tipe' => 'percent', 'nilai' => 0.7], $data[$qris->id]['biaya_layanan']);
    }

    public function test_status_resource_includes_breakdown(): void
    {
        $transaksi = Transaksi::factory()->make(['subtotal' => 100000, 'biaya_layanan' => 5000, 'total_pembayaran' => 105000]);

        $payload = (new TransaksiStatusResource($transaksi))->toArray(Request::create('/'));

        $this->assertSame(100000, $payload['subtotal']);
        $this->assertSame(5000, $payload['biaya_layanan']);
        $this->assertSame(105000, $payload['total_pembayaran']);
    }

    private function paymentForm(array $fee): array
    {
        return $fee + [
            'type' => 'midtrans',
            'name' => 'BNI Virtual Account',
            'midtrans_payment_type' => 'bank_transfer',
            'midtrans_bank' => 'bni',
        ];
    }

    public function test_admin_creates_payment_with_percent_fee(): void
    {
        $this->actingAs(User::factory()->create());

        // ImageService asli menulis ke public/image/payment; test tidak boleh meninggalkan file di repo.
        $this->app->instance(ImageService::class, new class extends ImageService
        {
            public function __construct() {}

            public function compress(UploadedFile $file, string $directory, ?string $filename = null): string
            {
                return $directory.'/fake-logo.png';
            }
        });

        $this->post(route('payment.store'), $this->paymentForm([
            'image' => UploadedFile::fake()->image('bni.png'),
            'biaya_layanan_tipe' => 'percent',
            'biaya_layanan_persen' => '2.5',
            'biaya_layanan_nominal' => '',
        ]))->assertSessionHasNoErrors();

        $payment = Payment::where('name', 'BNI Virtual Account')->firstOrFail();
        $this->assertSame(['tipe' => 'percent', 'nilai' => 2.5], $payment->serviceFeeConfig());
    }

    public function test_admin_updates_payment_fee_and_can_disable_it(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();

        $this->put(route('payment.update', $payment), $this->paymentForm([
            'biaya_layanan_tipe' => 'fixed',
            'biaya_layanan_nominal' => '4000',
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['tipe' => 'fixed', 'nilai' => 4000.0], $payment->fresh()->serviceFeeConfig());

        $this->put(route('payment.update', $payment), $this->paymentForm([
            'biaya_layanan_tipe' => 'none',
            'biaya_layanan_nominal' => '4000',
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['tipe' => 'none', 'nilai' => 0.0], $payment->fresh()->serviceFeeConfig());
        $this->assertEquals(0, $payment->fresh()->biaya_layanan_nilai);
    }

    public function test_admin_fee_validation(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment();

        foreach ([
            ['biaya_layanan_tipe' => 'percent', 'biaya_layanan_persen' => '101'],
            ['biaya_layanan_tipe' => 'percent'],
            ['biaya_layanan_tipe' => 'fixed', 'biaya_layanan_nominal' => '-1'],
            ['biaya_layanan_tipe' => 'fixed', 'biaya_layanan_nominal' => '1500.5'],
        ] as $fee) {
            $this->put(route('payment.update', $payment), $this->paymentForm($fee))
                ->assertSessionHasErrors('biaya_layanan_nilai');
        }

        $this->put(route('payment.update', $payment), $this->paymentForm(['biaya_layanan_tipe' => 'gratis']))
            ->assertSessionHasErrors('biaya_layanan_tipe');

        $this->assertSame('none', $payment->fresh()->serviceFeeConfig()['tipe']);
    }

    public function test_admin_payment_pages_show_fee(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->payment('fixed', 4000);

        $this->get(route('payment.index'))->assertOk()->assertSee('Rp4.000 / tiket');
        $this->get(route('payment.edit', $payment))->assertOk()->assertSee('Biaya Layanan')->assertSee('value="4000"', false);
        $this->get(route('payment.create'))->assertOk()->assertSee('Nominal per tiket');
    }
}
