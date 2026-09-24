<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiStatusTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_rejects_missing_or_wrong_token_for_tokened_transaction(): void
    {
        $event = Event::factory()->create(['status' => true]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
        ]);
        $transaksi = Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-TOKENED',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'public_token' => 'correct-token',
        ]);

        $this->getJson("/api/transaksi/{$transaksi->invoice}")
            ->assertForbidden();

        $this->getJson("/api/transaksi/{$transaksi->invoice}?token=wrong-token")
            ->assertForbidden();
    }

    public function test_status_endpoint_accepts_correct_token(): void
    {
        $event = Event::factory()->create(['status' => true]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
        ]);
        $transaksi = Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-TOKENED',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'public_token' => 'correct-token',
        ]);

        $this->getJson("/api/transaksi/{$transaksi->invoice}?token=correct-token")
            ->assertOk()
            ->assertJsonPath('data.invoice', 'INV-TOKENED');
    }

    public function test_status_endpoint_keeps_invoice_only_compatibility_for_legacy_transactions(): void
    {
        $event = Event::factory()->create(['status' => true]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
        ]);
        $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-LEGACY',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'public_token' => null,
        ]));

        $this->getJson("/api/transaksi/{$transaksi->invoice}")
            ->assertOk()
            ->assertJsonPath('data.invoice', 'INV-LEGACY');
    }
}
