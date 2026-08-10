<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiPengunjungDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_data_is_stored_and_cast_as_array(): void
    {
        $event = Event::factory()->create();
        $payment = Payment::factory()->create();

        $transaksi = Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-TEST-001',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 100000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'pengunjung_data' => [
                ['name' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'telepon' => '+6281234567890', 'jenis_kelamin' => 'Laki-laki'],
            ],
        ]);

        $fresh = $transaksi->fresh();

        $this->assertIsArray($fresh->pengunjung_data);
        $this->assertSame('Budi Santoso', $fresh->pengunjung_data[0]['name']);
    }
}
