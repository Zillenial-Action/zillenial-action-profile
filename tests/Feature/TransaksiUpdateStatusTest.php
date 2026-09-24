<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiUpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_api_checkout_transaction_success_and_volunteers_are_materialized(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['jumlah_tiket' => 5, 'status' => true]);
        $payment = Payment::factory()->create();
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_payment' => $payment->id,
            'status_pembayaran' => 'Pending',
            'pengunjung_data' => [
                ['name' => 'Budi', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
        ]);

        $response = $this->postJson(route('transaksi.update-status'), [
            'id' => $transaksi->id,
            'status' => 'Success',
        ]);

        $response->assertOk();
        $transaksi->refresh();
        $this->assertSame('Success', $transaksi->status_pembayaran);
        $this->assertSame(1, $transaksi->volunteers()->count());
        $this->assertDatabaseHas('volunteers', ['email' => 'budi@gmail.com']);
    }

    public function test_admin_cannot_mark_legacy_transaction_without_volunteers_or_pengunjung_data_success(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['jumlah_tiket' => 5, 'status' => true]);
        $payment = Payment::factory()->create();
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_payment' => $payment->id,
            'status_pembayaran' => 'Pending',
            'pengunjung_data' => null,
        ]);

        $response = $this->postJson(route('transaksi.update-status'), [
            'id' => $transaksi->id,
            'status' => 'Success',
        ]);

        $response->assertStatus(400);
        $this->assertSame('Pending', $transaksi->fresh()->status_pembayaran);
    }
}
