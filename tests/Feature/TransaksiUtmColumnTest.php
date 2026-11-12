<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiUtmColumnTest extends TestCase
{
    use RefreshDatabase;

    private function transaksi(string $invoice, array $utm = []): Transaksi
    {
        return Transaksi::create($utm + [
            'id_event' => Event::factory()->create()->id,
            'invoice' => $invoice,
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => Payment::factory()->create()->id,
        ]);
    }

    public function test_transaksi_table_shows_utm_source_medium_and_campaign(): void
    {
        $this->actingAs(User::factory()->create());

        $this->transaksi('INV-UTM-IG', [
            'utm_source' => 'instagram',
            'utm_medium' => 'story',
            'utm_campaign' => 'social-trip-garut',
        ]);
        $this->transaksi('INV-UTM-NONE');

        $this->get(route('transaksi.index'))
            ->assertOk()
            ->assertSee('Sumber (UTM)')
            ->assertSee('instagram')
            ->assertSee('/ story')
            ->assertSee('social-trip-garut')
            ->assertSee('Tanpa UTM');
    }
}
