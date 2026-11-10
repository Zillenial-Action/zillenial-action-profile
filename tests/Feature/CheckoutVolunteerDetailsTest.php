<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutVolunteerDetailsTest extends TestCase
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

        $this->app->instance(MidtransService::class, new class extends MidtransService {
            public function __construct() {}

            public function createSnapToken(Transaksi $transaksi): string
            {
                return 'fake-snap-token';
            }
        });
    }

    private function event(bool $adaBaju): Event
    {
        return Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
            'ada_baju' => $adaBaju,
        ]);
    }

    private function checkout(array $attendee): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/checkout', [
            'event_slug' => 'social-trip',
            'jumlah_tiket' => 1,
            'payment_method_id' => $this->payment->id,
            'pengunjung' => [
                array_merge(['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => 'budi@gmail.com'], $attendee),
            ],
        ]);
    }

    public function test_domisili_is_required(): void
    {
        $this->event(false);

        $this->checkout([])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pengunjung.0.domisili');
    }

    public function test_shirt_event_requires_a_valid_size(): void
    {
        $this->event(true);

        $this->checkout(['domisili' => 'Bandung'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pengunjung.0.ukuran_baju');

        $this->checkout(['domisili' => 'Bandung', 'ukuran_baju' => 'XS'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pengunjung.0.ukuran_baju');
    }

    public function test_shirt_size_is_stored_per_transaction_and_domisili_on_volunteer(): void
    {
        $this->event(true);

        $response = $this->checkout(['domisili' => '  Bandung ', 'ukuran_baju' => 'XL'])->assertOk();

        $transaksi = Transaksi::where('invoice', $response->json('order_id'))->firstOrFail();
        $this->assertSame('Bandung', $transaksi->pengunjung_data[0]['domisili']);
        $this->assertSame('XL', $transaksi->pengunjung_data[0]['ukuran_baju']);

        app(CheckoutService::class)->materializeVolunteers($transaksi);

        $this->assertDatabaseHas('volunteers', ['email' => 'budi@gmail.com', 'domisili' => 'Bandung']);
        $this->assertSame('XL', $transaksi->volunteers()->first()->pivot->ukuran_baju);
    }

    public function test_shirt_size_is_ignored_for_event_without_shirt(): void
    {
        $this->event(false);

        $response = $this->checkout(['domisili' => 'Garut', 'ukuran_baju' => 'XL'])->assertOk();

        $transaksi = Transaksi::where('invoice', $response->json('order_id'))->firstOrFail();
        $this->assertArrayNotHasKey('ukuran_baju', $transaksi->pengunjung_data[0]);
    }

    public function test_returning_volunteer_gets_latest_domisili(): void
    {
        $this->event(false);

        foreach (['Garut', 'Bandung'] as $domisili) {
            $response = $this->checkout(['domisili' => $domisili])->assertOk();
            $transaksi = Transaksi::where('invoice', $response->json('order_id'))->firstOrFail();
            app(CheckoutService::class)->materializeVolunteers($transaksi);
        }

        $this->assertDatabaseHas('volunteers', ['email' => 'budi@gmail.com', 'domisili' => 'Bandung']);
        $this->assertDatabaseCount('volunteers', 1);
    }

    public function test_event_detail_api_exposes_shirt_sizes_only_for_shirt_events(): void
    {
        $this->event(true);

        $this->getJson('/api/events/social-trip')
            ->assertOk()
            ->assertJsonPath('data.ada_baju', true)
            ->assertJsonPath('data.ukuran_baju', ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']);
    }

    public function test_admin_can_toggle_shirt_flag_on_event(): void
    {
        $this->actingAs(User::factory()->create());
        $event = $this->event(false);

        $payload = [
            'name' => $event->name,
            'mitra' => 'Zillenial Action',
            'waktu_mulai' => now()->addWeek()->format('Y-m-d H:i:s'),
            'waktu_berakhir' => now()->addWeek()->addHours(3)->format('Y-m-d H:i:s'),
            'nama_tempat' => 'Bandung',
            'alamat' => 'Jl. Asia Afrika',
            'kota' => 'Bandung',
            'jumlah_tiket' => 20,
            'harga' => 150000,
        ];

        $this->put(route('event.update', $event), $payload + ['ada_baju' => '1'])->assertRedirect();
        $this->assertTrue($event->fresh()->ada_baju);

        // Checkbox tidak dicentang: hanya hidden input "0" yang terkirim (atau tidak ada sama sekali).
        $this->put(route('event.update', $event->fresh()), $payload)->assertRedirect();
        $this->assertFalse($event->fresh()->ada_baju);
    }
}
