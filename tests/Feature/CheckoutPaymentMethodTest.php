<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use Tests\TestCase;

/**
 * Checkout portal memakai Midtrans Snap: PortalController mengirim satu metode Snap
 * (atau null), channel pembayaran dipilih di dalam popup Snap, bukan di checkout.
 */
class CheckoutPaymentMethodTest extends TestCase
{
    private function event(): Event
    {
        return new Event([
            'id' => 10,
            'name' => 'Social Trip',
            'slug' => 'social-trip',
            'mitra' => 'Zillenial Action',
            'waktu_mulai' => now()->addWeek(),
            'nama_tempat' => 'Bandung',
            'status' => true,
            'jumlah_tiket' => 10,
            'harga' => 150000,
        ]);
    }

    public function test_checkout_shows_single_midtrans_snap_option(): void
    {
        $event = $this->event();
        $payment = new Payment([
            'name' => 'Midtrans',
            'image' => 'assets/img/payment/midtrans.png',
            'no_rek' => '1234567890',
            'type' => 'midtrans',
            'status' => true,
        ]);
        $payment->id = 2;

        $this->withViewErrors([]);

        $response = $this->view('portal.checkout', compact('event', 'payment') + [
            'data' => $event,
        ]);

        $response->assertSee('Metode Pembayaran');
        $response->assertSee('Bayar via Midtrans');
        $response->assertDontSee('name="payment"', false);
        $response->assertDontSee('1234567890');
        $response->assertDontSee('Metode pembayaran Midtrans belum tersedia');
    }

    public function test_checkout_warns_when_snap_payment_method_is_missing(): void
    {
        $event = $this->event();
        $payment = null;

        $this->withViewErrors([]);

        $response = $this->view('portal.checkout', compact('event', 'payment') + [
            'data' => $event,
        ]);

        $response->assertSee('Metode pembayaran Midtrans belum tersedia');
        $response->assertDontSee('Bayar via Midtrans');
    }
}
