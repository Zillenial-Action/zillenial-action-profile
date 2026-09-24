<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use Tests\TestCase;

class InvoiceViewTest extends TestCase
{
    public function test_invoice_hides_payment_method_details(): void
    {
        $event = new Event([
            'id' => 10,
            'name' => 'Social Trip',
            'waktu_mulai' => now()->addWeek(),
            'waktu_berakhir' => now()->addWeek()->addHours(3),
            'nama_tempat' => 'Bandung',
            'image' => 'assets/img/event.png',
        ]);
        $payment = new Payment([
            'name' => 'Bank Manual',
            'image' => 'assets/img/payment.png',
            'no_rek' => '1234567890',
            'type' => 'manual',
        ]);
        $data = new Transaksi([
            'invoice' => 'INV-PENDING',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'snap_token' => 'snap-token',
        ]);
        $data->setRelation('event', $event);
        $data->setRelation('payment', $payment);
        $data->setRelation('volunteers', collect());

        $response = $this->view('portal.invoice', compact('data'));

        $response->assertSee('Metode Pembayaran');
        $response->assertSee('Midtrans');
        $response->assertDontSee('Bank Manual');
        $response->assertDontSee('1234567890');
        $response->assertDontSee('Transfer Bank, GoPay, OVO, QRIS');
    }
}
