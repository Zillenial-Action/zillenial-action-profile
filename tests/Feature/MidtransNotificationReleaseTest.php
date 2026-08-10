<?php

namespace Tests\Feature;

use App\Mail\SendTicket;
use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Payment;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MidtransNotificationReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function notify(string $invoice, string $transactionStatus, string $statusCode, string $gross): \Illuminate\Testing\TestResponse
    {
        $serverKey = 'server-test-key';
        config(['midtrans.server_key' => $serverKey]);

        $signature = hash('sha512', $invoice.$statusCode.$gross.$serverKey);

        return $this->postJson('/midtrans/notification', [
            'order_id' => $invoice,
            'transaction_status' => $transactionStatus,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'signature_key' => $signature,
            'fraud_status' => 'accept',
        ]);
    }

    public function test_failed_notification_restores_ticket_stock_and_voucher_quota(): void
    {
        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        $voucher = KodeVoucher::factory()->create([
            'id_event' => $event->id,
            'kuota' => 5,
            'digunakan' => 2, // 2 sudah terpakai oleh transaksi ini
        ]);
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => $voucher->id,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
        ]);

        $this->notify($transaksi->invoice, 'expire', '202', '100000.00')
            ->assertStatus(200);

        $this->assertSame('Failed', $transaksi->fresh()->status_pembayaran);
        $this->assertSame(10, $event->fresh()->jumlah_tiket, 'Stok tiket harus dikembalikan.');
        $this->assertSame(0, $voucher->fresh()->digunakan, 'Kuota voucher harus dikembalikan.');
    }

    public function test_failed_notification_is_idempotent_and_does_not_double_restore(): void
    {
        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => null,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
        ]);

        $this->notify($transaksi->invoice, 'expire', '202', '100000.00')->assertStatus(200);
        // Notifikasi kedua tidak boleh menambah stok lagi.
        $this->notify($transaksi->invoice, 'cancel', '202', '100000.00')->assertStatus(200);

        $this->assertSame(10, $event->fresh()->jumlah_tiket);
    }

    public function test_success_notification_marks_success_and_queues_ticket_email(): void
    {
        Mail::fake();

        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => null,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
            'pengunjung_data' => [
                ['name' => 'Budi', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890'],
            ],
        ]);

        $this->notify($transaksi->invoice, 'settlement', '200', '100000.00')
            ->assertStatus(200);

        $transaksi->refresh();
        $this->assertSame('Success', $transaksi->status_pembayaran);
        $this->assertNotNull($transaksi->tanggal_pembayaran);
        // Volunteer baru dibuat sekarang, setelah status Success.
        $this->assertSame(1, $transaksi->volunteers()->count());
        $this->assertDatabaseHas('volunteers', ['email' => 'budi@gmail.com']);
        // Stok TIDAK dikembalikan saat sukses.
        $this->assertSame(8, $event->fresh()->jumlah_tiket);
        Mail::assertQueued(SendTicket::class);
    }
}
