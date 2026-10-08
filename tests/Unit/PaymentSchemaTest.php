<?php

namespace Tests\Unit;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * no_rek dibuat nullable oleh migration 2026_06_29_000001_make_no_rek_nullable_on_payments_table
     * (metode Midtrans tidak punya nomor rekening). Cek hasil skemanya, bukan isi file migration.
     */
    public function test_payment_account_number_is_nullable(): void
    {
        $payment = Payment::factory()->create(['no_rek' => null, 'type' => 'midtrans']);

        $this->assertNull($payment->fresh()->no_rek);
    }
}
