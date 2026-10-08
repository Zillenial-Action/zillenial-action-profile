<?php

namespace Tests\Feature;

use App\Mail\SendTicket;
use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_marking_already_success_transaction_does_not_resend_ticket_emails(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['jumlah_tiket' => 5, 'status' => true]);
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_payment' => Payment::factory()->create()->id,
            'status_pembayaran' => 'Pending',
            'pengunjung_data' => [
                ['name' => 'Budi', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
        ]);

        $payload = ['id' => $transaksi->id, 'status' => 'Success'];
        $this->postJson(route('transaksi.update-status'), $payload)->assertOk();
        // Klik kedua: ditolak, tanpa email tambahan.
        $this->postJson(route('transaksi.update-status'), $payload)->assertStatus(409);

        Mail::assertQueued(SendTicket::class, 1);
        $this->assertSame(1, $transaksi->fresh()->volunteers()->count());
    }

    public function test_failed_to_success_reclaims_stock_and_voucher_quota(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['jumlah_tiket' => 5, 'status' => true]);
        $voucher = KodeVoucher::factory()->create(['id_event' => $event->id, 'kuota' => 5, 'digunakan' => 0]);
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_payment' => Payment::factory()->create()->id,
            'id_voucher' => $voucher->id,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Failed',
            'pengunjung_data' => [
                ['name' => 'Budi', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
        ]);

        $this->postJson(route('transaksi.update-status'), ['id' => $transaksi->id, 'status' => 'Success'])
            ->assertOk();

        $this->assertSame('Success', $transaksi->fresh()->status_pembayaran);
        $this->assertSame(3, $event->fresh()->jumlah_tiket);
        $this->assertSame(2, $voucher->fresh()->digunakan);
    }

    public function test_failed_to_success_is_rejected_when_stock_is_no_longer_available(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['jumlah_tiket' => 1, 'status' => true]);
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_payment' => Payment::factory()->create()->id,
            'id_voucher' => null,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Failed',
            'pengunjung_data' => [
                ['name' => 'Budi', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
        ]);

        $this->postJson(route('transaksi.update-status'), ['id' => $transaksi->id, 'status' => 'Success'])
            ->assertStatus(500)
            ->assertJsonFragment(['message' => 'Terjadi kesalahan saat memproses transaksi: Stok tiket tidak mencukupi untuk mengaktifkan kembali transaksi ini.']);

        $this->assertSame('Failed', $transaksi->fresh()->status_pembayaran);
        $this->assertSame(1, $event->fresh()->jumlah_tiket);
        $this->assertSame(0, $transaksi->fresh()->volunteers()->count());
    }
}
