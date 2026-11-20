<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiUkuranBajuColumnTest extends TestCase
{
    use RefreshDatabase;

    private function transaksi(string $invoice, string $status, ?array $pengunjung = null): Transaksi
    {
        return Transaksi::create([
            'id_event' => Event::factory()->create()->id,
            'invoice' => $invoice,
            'jumlah_tiket' => $pengunjung ? count($pengunjung) : 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => $status,
            'tanggal_register' => now(),
            'id_payment' => Payment::factory()->create()->id,
            'pengunjung_data' => $pengunjung,
        ]);
    }

    public function test_pending_transaction_shows_sizes_from_pengunjung_data(): void
    {
        $this->actingAs(User::factory()->create());

        $this->transaksi('INV-PENDING', 'Pending', [
            ['name' => 'Rina Pending', 'email' => 'rina@gmail.com', 'telepon' => '81234567890', 'ukuran_baju' => 'XS'],
            ['name' => 'Dodi Pending', 'email' => 'dodi@gmail.com', 'telepon' => '81234567891', 'ukuran_baju' => 'XL'],
        ]);

        $this->get(route('transaksi.index'))
            ->assertOk()
            ->assertSee('Ukuran Baju')
            ->assertSeeInOrder(['Rina Pending', 'XS', 'Dodi Pending', 'XL']);
    }

    public function test_paid_transaction_shows_sizes_from_volunteer_pivot(): void
    {
        $this->actingAs(User::factory()->create());

        $transaksi = $this->transaksi('INV-SUCCESS', 'Success');
        $volunteer = Volunteer::create(['name' => 'Sari Lunas', 'email' => 'sari@gmail.com', 'telepon' => '+6281234567892']);
        $transaksi->volunteers()->attach($volunteer->id, ['ukuran_baju' => 'M']);

        $this->assertSame([['name' => 'Sari Lunas', 'ukuran' => 'M']], $transaksi->load('volunteers')->ukuranBajuPeserta());
        $this->get(route('transaksi.index'))->assertOk()->assertSeeInOrder(['Sari Lunas', 'M']);
    }

    public function test_event_without_shirt_shows_dash(): void
    {
        $transaksi = $this->transaksi('INV-NOSHIRT', 'Pending', [
            ['name' => 'Tanpa Baju', 'email' => 'tb@gmail.com', 'telepon' => '81234567893'],
        ]);

        $this->assertSame([], $transaksi->load('volunteers')->ukuranBajuPeserta());
    }
}
