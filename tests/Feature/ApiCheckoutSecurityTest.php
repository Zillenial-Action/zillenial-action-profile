<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ApiCheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Payment $payment, array $overrides = []): array
    {
        return array_replace_recursive([
            'event_slug' => 'social-trip',
            'jumlah_tiket' => 1,
            'payment_method_id' => $payment->id,
            'pengunjung' => [
                ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => 'budi@gmail.com'],
            ],
        ], $overrides);
    }

    public function test_api_checkout_rejects_active_manual_payment_method(): void
    {
        Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
        ]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'manual',
            'no_rek' => '1234567890',
        ]);

        $response = $this->postJson('/api/checkout', $this->payload($payment));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('payment_method_id');
    }

    public function test_api_checkout_rejects_ticket_and_attendee_count_mismatch(): void
    {
        Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
        ]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
            'midtrans_bank' => 'bca',
        ]);

        $response = $this->postJson('/api/checkout', $this->payload($payment, [
            'jumlah_tiket' => 2,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('pengunjung');
    }

    public function test_core_api_payment_failure_rolls_back_transaction_and_stock(): void
    {
        $this->app->instance(MidtransService::class, new class extends MidtransService {
            public function __construct() {}

            public function charge(\App\Models\Transaksi $transaksi, Payment $payment): array
            {
                throw new RuntimeException('Midtrans unavailable.');
            }
        });

        $event = Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
        ]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
            'midtrans_bank' => 'bca',
        ]);

        $response = $this->postJson('/api/checkout', $this->payload($payment));

        $response->assertStatus(409);
        $this->assertDatabaseCount('transaksis', 0);
        $this->assertSame(10, $event->fresh()->jumlah_tiket);
    }

    public function test_snap_payment_failure_rolls_back_transaction_and_stock(): void
    {
        $this->app->instance(MidtransService::class, new class extends MidtransService {
            public function __construct() {}

            public function createSnapToken(\App\Models\Transaksi $transaksi): string
            {
                throw new RuntimeException('Snap unavailable.');
            }
        });

        $event = Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
        ]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => null,
        ]);

        $response = $this->postJson('/api/checkout', $this->payload($payment));

        $response->assertStatus(409);
        $this->assertDatabaseCount('transaksis', 0);
        $this->assertSame(10, $event->fresh()->jumlah_tiket);
    }

    public function test_api_checkout_returns_public_access_token_for_new_order(): void
    {
        $this->app->instance(MidtransService::class, new class extends MidtransService {
            public function __construct() {}

            public function charge(\App\Models\Transaksi $transaksi, Payment $payment): array
            {
                return [
                    'bank' => 'bca',
                    'va_number' => '12345678901',
                    'expiry_time' => now()->addDay()->format('Y-m-d H:i:s'),
                ];
            }
        });

        Event::factory()->create([
            'slug' => 'social-trip',
            'status' => true,
            'jumlah_tiket' => 10,
        ]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
            'midtrans_bank' => 'bca',
        ]);

        $response = $this->postJson('/api/checkout', $this->payload($payment));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertIsString($response->json('access_token'));
        $this->assertNotSame('', $response->json('access_token'));
        $this->assertDatabaseHas('transaksis', [
            'invoice' => $response->json('order_id'),
            'public_token' => $response->json('access_token'),
        ]);
    }
}
