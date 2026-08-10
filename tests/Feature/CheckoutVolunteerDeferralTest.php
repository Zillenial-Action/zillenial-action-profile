<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutVolunteerDeferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_stores_pengunjung_data_without_creating_volunteers(): void
    {
        $event = Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
        ]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => null, // Snap flow, no external HTTP assertions needed
        ]);

        $this->app->instance(\App\Services\MidtransService::class, new class extends \App\Services\MidtransService {
            public function __construct() {}

            public function createSnapToken(\App\Models\Transaksi $transaksi): string
            {
                return 'fake-snap-token';
            }
        });

        $response = $this->postJson('/api/checkout', [
            'event_slug' => 'social-trip',
            'jumlah_tiket' => 1,
            'payment_method_id' => $payment->id,
            'pengunjung' => [
                ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => 'budi@gmail.com'],
            ],
        ]);

        $response->assertOk();

        $transaksi = \App\Models\Transaksi::where('invoice', $response->json('order_id'))->firstOrFail();

        $this->assertSame(0, $transaksi->volunteers()->count(), 'Volunteer belum boleh dibuat sebelum pembayaran Success.');
        $this->assertNotEmpty($transaksi->pengunjung_data);
        $this->assertSame('budi@gmail.com', $transaksi->pengunjung_data[0]['email']);
        $this->assertDatabaseCount('volunteers', 0);
    }

    public function test_materialize_volunteers_creates_and_attaches_from_pengunjung_data(): void
    {
        $event = Event::factory()->create();
        $payment = Payment::factory()->create();

        $transaksi = \App\Models\Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-MAT-001',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 100000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'pengunjung_data' => [
                ['name' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890', 'jenis_kelamin' => 'Laki-laki'],
            ],
        ]);

        $service = app(\App\Services\CheckoutService::class);
        $volunteers = $service->materializeVolunteers($transaksi);

        $this->assertCount(1, $volunteers);
        $this->assertDatabaseHas('volunteers', ['email' => 'budi@gmail.com', 'jenis_kelamin' => 'Laki-laki']);
        $this->assertSame(1, $transaksi->volunteers()->count());
    }

    public function test_materialize_volunteers_is_idempotent(): void
    {
        $event = Event::factory()->create();
        $payment = Payment::factory()->create();

        $transaksi = \App\Models\Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-MAT-002',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 100000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'pengunjung_data' => [
                ['name' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
        ]);

        $service = app(\App\Services\CheckoutService::class);
        $service->materializeVolunteers($transaksi);
        $service->materializeVolunteers($transaksi);

        $this->assertSame(1, $transaksi->volunteers()->count(), 'Panggilan kedua tidak boleh membuat duplikat.');
        $this->assertDatabaseCount('volunteers', 1);
    }
}
