<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Transaksi;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class MidtransServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_throws_for_unsupported_payment_type(): void
    {
        $payment = Payment::factory()->create([
            'type' => 'midtrans',
            'midtrans_payment_type' => 'unsupported_channel',
        ]);
        $transaksi = Transaksi::factory()->create(['id_payment' => $payment->id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Channel Midtrans tidak didukung: unsupported_channel.');

        (new MidtransService)->charge($transaksi, $payment);
    }

    public function test_parse_notification_accepts_valid_midtrans_signature(): void
    {
        config(['midtrans.server_key' => 'server-test-key']);

        $payload = [
            'order_id' => 'INV-001',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'gross_amount' => '1000.00',
            'status_code' => '200',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-test-key'
        );

        $request = Request::create('/midtrans/notification', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $notification = (new MidtransService)->parseNotification($request);

        $this->assertSame('INV-001', $notification['order_id']);
        $this->assertSame('settlement', $notification['transaction_status']);
        $this->assertSame('accept', $notification['fraud_status']);
        $this->assertSame('bank_transfer', $notification['payment_type']);
        $this->assertSame('1000.00', $notification['gross_amount']);
    }

    public function test_parse_notification_rejects_invalid_midtrans_signature(): void
    {
        config(['midtrans.server_key' => 'server-test-key']);

        $request = Request::create('/midtrans/notification', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'order_id' => 'INV-001',
            'transaction_status' => 'settlement',
            'gross_amount' => '1000.00',
            'status_code' => '200',
            'signature_key' => 'invalid-signature',
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Midtrans signature.');

        (new MidtransService)->parseNotification($request);
    }
}
