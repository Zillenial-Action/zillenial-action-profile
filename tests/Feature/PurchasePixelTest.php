<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use Tests\TestCase;

class PurchasePixelTest extends TestCase
{
    private function transaksi(string $status): Transaksi
    {
        $data = new Transaksi([
            'invoice' => 'INV-PIXEL',
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'status_pembayaran' => $status,
            'tanggal_register' => now(),
            'snap_token' => 'snap-token',
        ]);
        $data->setRelation('event', new Event([
            'name' => 'Social Trip',
            'waktu_mulai' => now()->addWeek(),
            'waktu_berakhir' => now()->addWeek()->addHours(3),
            'nama_tempat' => 'Bandung',
            'image' => 'assets/img/event.png',
        ]));
        $data->setRelation('payment', new Payment(['name' => 'Midtrans', 'type' => 'midtrans']));
        $data->setRelation('volunteers', collect());

        return $data;
    }

    public function test_pending_invoice_does_not_track_purchase(): void
    {
        $response = $this->view('portal.invoice', ['data' => $this->transaksi('Pending')]);

        $response->assertDontSee("'Purchase'", false);
        $response->assertDontSee("'CompletePayment'", false);
        $response->assertSee("fbq('track', 'InitiateCheckout'", false);
    }

    public function test_payment_success_tracks_purchase_only_when_success(): void
    {
        $this->view('portal.payment-success', ['data' => $this->transaksi('Success')])
            ->assertSee("fbq('track', 'Purchase'", false)
            ->assertSee('"purchase-INV-PIXEL"', false);

        $this->view('portal.payment-success', ['data' => $this->transaksi('Pending')])
            ->assertDontSee("'Purchase'", false);
    }

    public function test_pending_payment_success_polls_status_until_webhook_arrives(): void
    {
        // Snap redirect bisa tiba sebelum webhook; halaman harus cek ulang status agar Purchase tetap terkirim.
        $this->view('portal.payment-success', ['data' => $this->transaksi('Pending')])
            // @json meng-escape "/" menjadi "\/".
            ->assertSee('api\/transaksi\/INV-PIXEL', false)
            ->assertSee('window.location.reload()', false);

        $this->view('portal.payment-success', ['data' => $this->transaksi('Success')])
            ->assertDontSee('api\/transaksi\/INV-PIXEL', false);
    }
}
