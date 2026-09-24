<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRemediationBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_backfills_missing_transaction_public_tokens(): void
    {
        $event = Event::factory()->create(['status' => true]);
        $payment = Payment::factory()->create(['status' => true, 'type' => 'midtrans']);
        $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
            'id_event' => $event->id,
            'invoice' => 'INV-LEGACY-TOKEN',
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
            'public_token' => null,
        ]));

        $this->artisan('security:remediation-backfill', ['--tokens-only' => true])
            ->assertSuccessful();

        $this->assertNotEmpty($transaksi->fresh()->public_token);
    }

    public function test_command_sanitizes_existing_event_descriptions(): void
    {
        $event = Event::factory()->create([
            'deskripsi' => '<h2>Aman</h2><p><img src=x onerror=alert(1)>Halo<script>alert(1)</script></p>',
        ]);

        $this->artisan('security:remediation-backfill', ['--descriptions-only' => true])
            ->assertSuccessful();

        $description = $event->fresh()->deskripsi;

        $this->assertStringContainsString('<h2>Aman</h2>', $description);
        $this->assertStringNotContainsString('<script', $description);
        $this->assertStringNotContainsString('onerror', $description);
    }

    public function test_dry_run_does_not_write_changes(): void
    {
        $event = Event::factory()->create([
            'deskripsi' => '<p onclick="alert(1)">Halo</p>',
        ]);

        $this->artisan('security:remediation-backfill', [
            '--descriptions-only' => true,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame('<p onclick="alert(1)">Halo</p>', $event->fresh()->deskripsi);
    }
}
