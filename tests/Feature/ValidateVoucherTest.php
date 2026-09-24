<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\KodeVoucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidateVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_voucher_returns_discount_without_calling_external(): void
    {
        Http::fake(); // gagalkan test bila ada panggilan external tak terduga
        $event = Event::factory()->create(['harga' => 100000]);
        KodeVoucher::factory()->create([
            'id_event' => $event->id,
            'kode' => 'LOCAL10',
            'nilai_diskon' => 10000,
            'status' => true,
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code' => 'LOCAL10',
            'event_id' => $event->id,
            'jumlah_tiket' => 2,
        ]);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'discount_per_ticket' => 10000,
            'is_external' => false,
        ]);
        Http::assertNothingSent();
    }

    public function test_external_valid_creates_voucher_and_returns_discount(): void
    {
        $event = Event::factory()->create(['harga' => 200000]);
        Http::fake([
            '*chatkebaikan*' => Http::response([
                'valid' => true,
                'discount_percent' => 25,
                'reward_name' => 'Hadiah ChatBaik',
                'berlaku_sampai' => now()->addDays(10)->toDateString(),
            ], 200),
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code' => 'CHATBAIK-ABC123',
            'event_id' => $event->id,
            'jumlah_tiket' => 1,
        ]);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'discount_per_ticket' => 50000, // 25% dari 200000
            'is_external' => true,
        ]);
        $this->assertDatabaseHas('kode_vouchers', [
            'kode' => 'CHATBAIK-ABC123',
            'is_external' => true,
            'kuota' => 1,
        ]);
    }

    public function test_external_rejected_when_more_than_one_ticket(): void
    {
        $event = Event::factory()->create(['harga' => 200000]);
        Http::fake([
            '*chatkebaikan*' => Http::response([
                'valid' => true,
                'discount_percent' => 25,
                'reward_name' => 'Hadiah',
                'berlaku_sampai' => now()->addDays(10)->toDateString(),
            ], 200),
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code' => 'CHATBAIK-XYZ',
            'event_id' => $event->id,
            'jumlah_tiket' => 2,
        ]);

        $response->assertStatus(400)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('kode_vouchers', ['kode' => 'CHATBAIK-XYZ']);
    }

    public function test_external_invalid_returns_reason(): void
    {
        $event = Event::factory()->create();
        Http::fake([
            '*chatkebaikan*' => Http::response([
                'valid' => false,
                'reason' => 'Kode sudah dipakai.',
            ], 200),
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code' => 'BADCODE',
            'event_id' => $event->id,
            'jumlah_tiket' => 1,
        ]);

        $response->assertStatus(400)->assertJson([
            'success' => false,
            'message' => 'Kode sudah dipakai.',
        ]);
        $this->assertDatabaseMissing('kode_vouchers', ['kode' => 'BADCODE']);
    }

    public function test_external_api_down_returns_friendly_error(): void
    {
        $event = Event::factory()->create();
        Http::fake([
            '*chatkebaikan*' => Http::response('', 500),
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code' => 'ANY',
            'event_id' => $event->id,
            'jumlah_tiket' => 1,
        ]);

        $response->assertStatus(400)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('kode_vouchers', ['kode' => 'ANY']);
    }
}
