<?php

namespace Tests\Feature;

use App\Mail\SendTicket;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TicketSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(Event $event): Transaksi
    {
        $payment = Payment::factory()->create(['status' => true, 'type' => 'midtrans']);

        return Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-TICKET-SETTINGS',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Success',
            'tanggal_register' => now(),
            'tanggal_pembayaran' => now(),
            'id_payment' => $payment->id,
            'public_token' => 'ticket-token',
        ]);
    }

    private function ticketPage(Transaksi $transaksi): \Illuminate\Testing\TestResponse
    {
        return $this->get("/tiket/{$transaksi->invoice}?token=ticket-token")->assertOk()->assertViewIs('portal.tiket');
    }

    public function test_ticket_uses_default_terms_and_contacts_when_nothing_configured(): void
    {
        $transaksi = $this->ticketFor(Event::factory()->create(['status' => true, 'syarat_tiket' => null]));

        $this->ticketPage($transaksi)
            ->assertSee('Tiket yang sudah dibeli tidak dapat dibatalkan')
            ->assertSee('https://wa.me/6282121392363', false)
            ->assertSee('zillenialaction@gmail.com');
    }

    public function test_event_terms_override_default_terms(): void
    {
        $transaksi = $this->ticketFor(Event::factory()->create([
            'status' => true,
            'syarat_tiket' => '<ul><li>Wajib membawa KTP</li></ul>',
        ]));

        $this->ticketPage($transaksi)
            ->assertSee('Wajib membawa KTP')
            ->assertDontSee('Tiket yang sudah dibeli tidak dapat dibatalkan');
    }

    public function test_admin_updates_settings_and_ticket_and_email_follow(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('ticket-setting.update'), [
            'ticket_terms' => '<ul><li>Datang 30 menit sebelum acara</li></ul><script>alert(1)</script><p><img src="https://x.test/a.jpg"></p>',
            'ticket_email_message' => '<p>Hai Sobat Zigi!</p>',
            'ticket_contact_whatsapp' => '0812 3456 7890',
            'ticket_contact_email' => '',
            'ticket_contact_website' => 'https://sostrip.example',
        ])->assertRedirect(route('ticket-setting.edit'));

        $settings = app(TicketSettings::class);
        $this->assertStringNotContainsString('<script', $settings->get('ticket_terms'));
        $this->assertStringNotContainsString('<img', $settings->get('ticket_terms'));

        $transaksi = $this->ticketFor(Event::factory()->create(['status' => true, 'syarat_tiket' => null]));

        $this->ticketPage($transaksi)
            ->assertSee('Datang 30 menit sebelum acara')
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('sostrip.example')
            ->assertDontSee('mailto:', false);

        $html = (new SendTicket($transaksi->invoice, 'https://example.test/tiket'))->render();
        $this->assertStringContainsString('Hai Sobat Zigi!', $html);
        $this->assertStringContainsString($transaksi->invoice, $html);
    }

    public function test_settings_page_requires_login_and_validates_input(): void
    {
        $this->get(route('ticket-setting.edit'))->assertRedirect();

        $this->actingAs(User::factory()->create());
        $this->get(route('ticket-setting.edit'))->assertOk()->assertSee('Pengaturan Tiket');

        $this->put(route('ticket-setting.update'), [
            'ticket_contact_whatsapp' => 'bukan-nomor',
            'ticket_contact_website' => 'bukan url',
        ])->assertSessionHasErrors(['ticket_contact_whatsapp', 'ticket_contact_website']);
    }

    public function test_event_ticket_terms_are_sanitized_and_empty_editor_falls_back_to_default(): void
    {
        $this->actingAs(User::factory()->create());
        $event = Event::factory()->create(['name' => 'Social Trip Garut']);

        $payload = [
            'name' => 'Social Trip Garut',
            'mitra' => 'Zillenial Action',
            'waktu_mulai' => now()->addWeek()->format('Y-m-d H:i:s'),
            'waktu_berakhir' => now()->addWeek()->addHours(3)->format('Y-m-d H:i:s'),
            'nama_tempat' => 'Garut',
            'alamat' => 'Jl. Raya',
            'kota' => 'Garut',
            'jumlah_tiket' => 20,
            'harga' => 150000,
        ];

        $this->put(route('event.update', $event), $payload + [
            'syarat_tiket' => '<p onclick="x()">Bawa botol minum</p><img src="https://x.test/a.jpg">',
        ])->assertRedirect();
        $this->assertSame('<p>Bawa botol minum</p>', $event->fresh()->syarat_tiket);

        $this->put(route('event.update', $event->fresh()), $payload + ['syarat_tiket' => '<p><br></p>'])->assertRedirect();
        $this->assertNull($event->fresh()->syarat_tiket);
    }
}
