<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutExternalRedeemTest extends TestCase
{
    use RefreshDatabase;

    private function checkoutPayload(Payment $payment): array
    {
        return [
            'jumlah_tiket' => 1,
            'price' => 150000,
            'payment' => $payment->id,
            'voucher_code' => 'CHATBAIK-RD1',
            'pengunjung' => [
                ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => 'budi@gmail.com'],
            ],
        ];
    }

    public function test_external_voucher_redeem_success_saves_transaction(): void
    {
        config(['services.chatkebaikan.redeem_url' => 'https://chatkebaikan.raihmimpi.id/api/dr/voucher/redeem']);
        Http::fake(['*redeem*' => Http::response(['ok' => true], 200)]);

        $event = Event::factory()->create(['harga' => 200000, 'jumlah_tiket' => 10]);
        $payment = Payment::factory()->create(['status' => true, 'type' => 'midtrans', 'no_rek' => 'MIDTRANS']);
        $voucher = KodeVoucher::factory()->external()->create([
            'id_event' => $event->id,
            'kode' => 'CHATBAIK-RD1',
            'nilai_diskon' => 50000,
            'digunakan' => 0,
        ]);

        $response = $this->post("/transaksi/post/{$event->slug}", $this->checkoutPayload($payment));

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', ['id_voucher' => $voucher->id]);
        $this->assertSame(1, $voucher->fresh()->digunakan);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'redeem'));
    }

    public function test_external_voucher_redeem_failure_rolls_back(): void
    {
        config(['services.chatkebaikan.redeem_url' => 'https://chatkebaikan.raihmimpi.id/api/dr/voucher/redeem']);
        Http::fake(['*redeem*' => Http::response('', 500)]);

        $event = Event::factory()->create(['harga' => 200000, 'jumlah_tiket' => 10]);
        $payment = Payment::factory()->create(['status' => true, 'type' => 'midtrans', 'no_rek' => 'MIDTRANS']);
        $voucher = KodeVoucher::factory()->external()->create([
            'id_event' => $event->id,
            'kode' => 'CHATBAIK-RD1',
            'nilai_diskon' => 50000,
            'digunakan' => 0,
        ]);

        $response = $this->post("/transaksi/post/{$event->slug}", $this->checkoutPayload($payment));

        $response->assertRedirect();
        // Rollback: tidak ada transaksi, kuota voucher tidak berubah, tiket event utuh.
        $this->assertDatabaseMissing('transaksis', ['id_voucher' => $voucher->id]);
        $this->assertSame(0, $voucher->fresh()->digunakan);
        $this->assertSame(10, $event->fresh()->jumlah_tiket);
    }
}
