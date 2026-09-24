<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTicketTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokened_ticket_route_rejects_missing_or_wrong_token(): void
    {
        $transaksi = $this->successTransaction('ticket-token');

        $this->get("/tiket/{$transaksi->invoice}")
            ->assertOk()
            ->assertViewIs('portal.error_tiket');

        $this->get("/tiket/{$transaksi->invoice}?token=wrong-token")
            ->assertOk()
            ->assertViewIs('portal.error_tiket');
    }

    public function test_tokened_ticket_route_accepts_correct_token(): void
    {
        $transaksi = $this->successTransaction('ticket-token');

        $this->get("/tiket/{$transaksi->invoice}?token=ticket-token")
            ->assertOk()
            ->assertViewIs('portal.tiket');
    }

    public function test_success_invoice_redirect_preserves_ticket_token(): void
    {
        $transaksi = $this->successTransaction('ticket-token');

        $this->get("/invoice/{$transaksi->invoice}?token=ticket-token")
            ->assertRedirect(route('portal.tiket', [
                'invoice' => $transaksi->invoice,
                'token' => 'ticket-token',
            ]));
    }

    private function successTransaction(string $token): Transaksi
    {
        $event = Event::factory()->create(['status' => true]);
        $payment = Payment::factory()->create(['status' => true, 'type' => 'midtrans']);

        return Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-TICKET-TOKEN',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Success',
            'tanggal_register' => now(),
            'tanggal_pembayaran' => now(),
            'id_payment' => $payment->id,
            'public_token' => $token,
        ]);
    }
}
