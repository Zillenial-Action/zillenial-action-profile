<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiAdminEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_renders(): void
    {
        $this->actingAs(User::factory()->create());

        $transaksi = Transaksi::factory()->create([
            'id_event' => Event::factory()->create(['name' => 'Social Trip Bandung'])->id,
            'id_payment' => Payment::factory()->create()->id,
        ]);

        $this->get(route('transaksi.edit', $transaksi))
            ->assertOk()
            ->assertSee('Social Trip Bandung')
            ->assertSee('name="email"', false)
            ->assertDontSee('name="jumlah_tiket"', false)
            ->assertDontSee('name="id_event"', false);
    }

    public function test_update_only_changes_contact_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $event = Event::factory()->create(['harga' => 100000, 'jumlah_tiket' => 10]);
        $otherEvent = Event::factory()->create(['harga' => 500000]);
        $payment = Payment::factory()->create();
        $transaksi = Transaksi::factory()->create([
            'id_event' => $event->id,
            'id_payment' => $payment->id,
            'jumlah_tiket' => 2,
            // Harga setelah diskon voucher, bukan jumlah_tiket x harga.
            'total_pembayaran' => 150000,
        ]);

        $this->put(route('transaksi.update', $transaksi), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'telepon' => '081200000000',
            'id_event' => $otherEvent->id,
            'jumlah_tiket' => 5,
            'total_pembayaran' => 1,
        ])->assertRedirect(route('transaksi.index'));

        $transaksi->refresh();
        $this->assertSame('Nama Baru', $transaksi->name);
        $this->assertSame('baru@example.com', $transaksi->email);
        $this->assertSame('081200000000', $transaksi->telepon);
        $this->assertSame($event->id, $transaksi->id_event);
        $this->assertSame(2, (int) $transaksi->jumlah_tiket);
        $this->assertSame(150000, (int) $transaksi->total_pembayaran);
        $this->assertSame(10, (int) $event->fresh()->jumlah_tiket);
    }
}
