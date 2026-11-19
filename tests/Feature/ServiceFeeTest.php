<?php

namespace Tests\Feature;

use App\Http\Resources\TransaksiStatusResource;
use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\ServiceFeeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ServiceFeeTest extends TestCase
{
    use RefreshDatabase;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => null,
        ]);

        $this->app->instance(MidtransService::class, new class extends MidtransService
        {
            public function __construct() {}

            public function createSnapToken(Transaksi $transaksi): string
            {
                return 'fake-snap-token';
            }
        });
    }

    private function fee(): ServiceFeeSettings
    {
        return app(ServiceFeeSettings::class);
    }

    private function checkout(int $qty, ?string $voucher = null): Transaksi
    {
        $pengunjung = [];
        for ($i = 1; $i <= $qty; $i++) {
            $pengunjung[] = ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => "budi{$i}@gmail.com", 'domisili' => 'Bandung'];
        }

        $response = $this->postJson('/api/checkout', [
            'event_slug' => 'social-trip',
            'jumlah_tiket' => $qty,
            'payment_method_id' => $this->payment->id,
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

    public function test_default_is_no_service_fee(): void
    {
        $this->assertSame(['tipe' => 'none', 'nilai' => 0.0], $this->fee()->config());
        $this->assertSame(0, $this->fee()->calculate(100000, 1)['amount']);
    }

    public function test_percent_is_based_on_subtotal_and_rounded_up(): void
    {
        $this->fee()->update('percent', 2.5);

        $this->assertSame(2500, $this->fee()->calculate(100000, 1)['amount']);
        // 99.999 x 2,5% = 2.499,975 -> dibulatkan ke atas
        $this->assertSame(2500, $this->fee()->calculate(99999, 1)['amount']);
        // galat float tidak boleh menambah Rp1: 100.000 x 1,1% = 1.100
        $this->fee()->update('percent', 1.1);
        $this->assertSame(1100, $this->fee()->calculate(100000, 1)['amount']);
    }

    public function test_fixed_is_charged_per_ticket(): void
    {
        $this->fee()->update('fixed', 5000);

        $result = $this->fee()->calculate(300000, 3);

        $this->assertSame(15000, $result['amount']);
        $this->assertSame('fixed', $result['tipe']);
        $this->assertSame(5000.0, $result['nilai']);
    }

    public function test_free_subtotal_has_no_service_fee(): void
    {
        $this->fee()->update('fixed', 5000);

        $this->assertSame(['amount' => 0, 'tipe' => null, 'nilai' => null], $this->fee()->calculate(0, 2));
    }

    public function test_checkout_adds_percent_fee_after_voucher_discount(): void
    {
        $event = $this->event(100000);
        KodeVoucher::factory()->create(['id_event' => $event->id, 'kode' => 'HEMAT', 'nilai_diskon' => 20000]);
        $this->fee()->update('percent', 5);

        $transaksi = $this->checkout(2, 'HEMAT');

        $this->assertSame(160000, $transaksi->subtotal);
        $this->assertSame(8000, $transaksi->biaya_layanan);
        $this->assertSame(168000, $transaksi->total_pembayaran);
        $this->assertSame('percent', $transaksi->biaya_layanan_tipe);
        $this->assertSame(5.0, $transaksi->biaya_layanan_nilai);
    }

    public function test_checkout_adds_fixed_fee_per_ticket(): void
    {
        $this->event(100000);
        $this->fee()->update('fixed', 4000);

        $transaksi = $this->checkout(3);

        $this->assertSame(300000, $transaksi->subtotal);
        $this->assertSame(12000, $transaksi->biaya_layanan);
        $this->assertSame(312000, $transaksi->total_pembayaran);
    }

    public function test_checkout_without_fee_keeps_previous_total(): void
    {
        $this->event(150000);

        $transaksi = $this->checkout(2);

        $this->assertSame(300000, $transaksi->subtotal);
        $this->assertSame(0, $transaksi->biaya_layanan);
        $this->assertNull($transaksi->biaya_layanan_tipe);
        $this->assertSame(300000, $transaksi->total_pembayaran);
    }

    public function test_changing_setting_does_not_change_existing_transaction(): void
    {
        $this->event(100000);
        $this->fee()->update('fixed', 5000);
        $transaksi = $this->checkout(1);

        $this->fee()->update('percent', 10);

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

    public function test_event_detail_api_exposes_fee_config(): void
    {
        $this->event();
        $this->fee()->update('percent', 2.5);

        $this->getJson('/api/events/social-trip')
            ->assertOk()
            ->assertJsonPath('data.biaya_layanan.tipe', 'percent')
            ->assertJsonPath('data.biaya_layanan.nilai', 2.5);
    }

    public function test_status_resource_includes_breakdown(): void
    {
        $transaksi = Transaksi::factory()->make(['subtotal' => 100000, 'biaya_layanan' => 5000, 'total_pembayaran' => 105000]);

        $payload = (new TransaksiStatusResource($transaksi))->toArray(Request::create('/'));

        $this->assertSame(100000, $payload['subtotal']);
        $this->assertSame(5000, $payload['biaya_layanan']);
        $this->assertSame(105000, $payload['total_pembayaran']);
    }

    public function test_admin_can_set_percent_fee(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('service-fee.update'), [
            'service_fee_type' => 'percent',
            'service_fee_percent' => '2.5',
            'service_fee_fixed' => '',
        ])->assertRedirect(route('service-fee.edit'))->assertSessionHasNoErrors();

        $this->assertSame(['tipe' => 'percent', 'nilai' => 2.5], $this->fee()->config());
    }

    public function test_admin_can_set_fixed_fee_and_disable_it(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('service-fee.update'), [
            'service_fee_type' => 'fixed',
            'service_fee_fixed' => '5000',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['tipe' => 'fixed', 'nilai' => 5000.0], $this->fee()->config());

        $this->put(route('service-fee.update'), ['service_fee_type' => 'none'])->assertSessionHasNoErrors();
        $this->assertSame(['tipe' => 'none', 'nilai' => 0.0], $this->fee()->config());
    }

    public function test_admin_fee_validation(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('service-fee.update'), ['service_fee_type' => 'percent', 'service_fee_percent' => '101'])
            ->assertSessionHasErrors('service_fee_percent');
        $this->put(route('service-fee.update'), ['service_fee_type' => 'percent'])
            ->assertSessionHasErrors('service_fee_percent');
        $this->put(route('service-fee.update'), ['service_fee_type' => 'fixed', 'service_fee_fixed' => '-1'])
            ->assertSessionHasErrors('service_fee_fixed');
        $this->put(route('service-fee.update'), ['service_fee_type' => 'gratis'])
            ->assertSessionHasErrors('service_fee_type');

        $this->assertSame('none', $this->fee()->config()['tipe']);
    }

    public function test_admin_page_requires_login_and_renders(): void
    {
        $this->get(route('service-fee.edit'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('service-fee.edit'))
            ->assertOk()
            ->assertSee('Biaya Layanan')
            ->assertSee('Nominal per tiket');
    }
}
