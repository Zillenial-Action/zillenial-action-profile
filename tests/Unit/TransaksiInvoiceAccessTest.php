<?php

namespace Tests\Unit;

use App\Models\Transaksi;
use Tests\TestCase;

class TransaksiInvoiceAccessTest extends TestCase
{
    public function test_success_transaction_cannot_access_invoice(): void
    {
        $transaksi = new Transaksi([
            'status_pembayaran' => 'Success',
            'tanggal_register' => now(),
        ]);

        $this->assertFalse($transaksi->canAccessInvoice());
    }

    public function test_pending_transaction_can_access_invoice_before_deadline(): void
    {
        $transaksi = new Transaksi([
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
        ]);

        $this->assertTrue($transaksi->canAccessInvoice());
    }

    public function test_old_pending_transaction_cannot_access_invoice(): void
    {
        $transaksi = new Transaksi([
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now()->subDays(2),
        ]);

        $this->assertFalse($transaksi->canAccessInvoice());
    }
}
