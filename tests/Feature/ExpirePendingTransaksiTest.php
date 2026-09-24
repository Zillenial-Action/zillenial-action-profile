<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePendingTransaksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_stale_pending_and_restores_stock_and_voucher(): void
    {
        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        $voucher = KodeVoucher::factory()->create([
            'id_event' => $event->id,
            'kuota' => 5,
            'digunakan' => 2,
        ]);

        $stale = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => $voucher->id,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
        ]);
        $stale->created_at = now()->subHours(30);
        $stale->save();

        $this->artisan('transaksi:expire-pending')->assertExitCode(0);

        $this->assertSame('Failed', $stale->fresh()->status_pembayaran);
        $this->assertSame(10, $event->fresh()->jumlah_tiket);
        $this->assertSame(0, $voucher->fresh()->digunakan);
    }

    public function test_does_not_expire_recent_pending(): void
    {
        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        $recent = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => null,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
        ]);
        $recent->created_at = now()->subHour();
        $recent->save();

        $this->artisan('transaksi:expire-pending')->assertExitCode(0);

        $this->assertSame('Pending', $recent->fresh()->status_pembayaran);
        $this->assertSame(8, $event->fresh()->jumlah_tiket);
    }

    public function test_expires_short_channel_using_expiry_time_after_grace(): void
    {
        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        // QRIS baru dibuat 20 menit lalu, tapi expiry_time-nya 20 menit lalu (sudah lewat + grace 15 mnt).
        $qris = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => null,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
            'payment_instructions' => ['expiry_time' => now()->subMinutes(20)->format('Y-m-d H:i:s')],
        ]);
        $qris->created_at = now()->subMinutes(20);
        $qris->save();

        $this->artisan('transaksi:expire-pending')->assertExitCode(0);

        $this->assertSame('Failed', $qris->fresh()->status_pembayaran);
        $this->assertSame(10, $event->fresh()->jumlah_tiket);
    }

    public function test_does_not_expire_before_expiry_time_plus_grace(): void
    {
        $event = Event::factory()->create(['jumlah_tiket' => 8, 'status' => true]);
        // expiry_time baru saja lewat, tapi masih dalam grace period → jangan dilepas.
        $qris = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_voucher' => null,
            'jumlah_tiket' => 2,
            'status_pembayaran' => 'Pending',
            'payment_instructions' => ['expiry_time' => now()->subMinutes(2)->format('Y-m-d H:i:s')],
        ]);
        $qris->created_at = now()->subMinutes(17);
        $qris->save();

        $this->artisan('transaksi:expire-pending')->assertExitCode(0);

        $this->assertSame('Pending', $qris->fresh()->status_pembayaran);
        $this->assertSame(8, $event->fresh()->jumlah_tiket);
    }
}
