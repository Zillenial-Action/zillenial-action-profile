<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\FundraiserProgram;
use App\Models\KodeVoucher;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundraiserTest extends TestCase
{
    use RefreshDatabase;

    private function asCustomer(Customer $customer): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($customer->createToken('sostrip')->plainTextToken);
    }

    private function fakeMidtrans(): void
    {
        $this->app->instance(MidtransService::class, new class extends MidtransService {
            public function __construct() {}

            public function charge(Transaksi $transaksi, Payment $payment): array
            {
                return ['bank' => 'bca', 'va_number' => '1234567890'];
            }
        });
    }

    private function midtransPayment(): Payment
    {
        return Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
            'midtrans_bank' => 'bca',
        ]);
    }

    private function checkout(Event $event, Payment $payment, string $kode, string $email = 'budi@gmail.com', int $tiket = 1, array $extra = [])
    {
        $pengunjung = [];
        for ($i = 0; $i < $tiket; $i++) {
            $pengunjung[] = ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => $i === 0 ? $email : "teman{$i}@gmail.com"];
        }

        return $this->postJson('/api/checkout', [
            'event_slug' => $event->slug,
            'jumlah_tiket' => $tiket,
            'payment_method_id' => $payment->id,
            'voucher_code' => $kode,
            'pengunjung' => $pengunjung,
        ] + $extra);
    }

    public function test_customer_generates_one_code_per_program_following_program_settings(): void
    {
        $program = FundraiserProgram::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Siti Aminah']);
        $this->asCustomer($customer);

        $first = $this->postJson("/api/fundraiser/{$program->id}/kode");
        $first->assertCreated()
            ->assertJsonPath('data.kode.kuota', 10)
            ->assertJsonPath('data.kode.digunakan', 0)
            ->assertJsonPath('data.nilai_komisi', 15000);
        $this->assertMatchesRegularExpression('/^SITI-[A-Z2-9]{4}$/', $first->json('data.kode.kode'));

        $second = $this->postJson("/api/fundraiser/{$program->id}/kode");
        $second->assertOk()->assertJsonPath('data.kode.kode', $first->json('data.kode.kode'));

        $voucher = KodeVoucher::where('id_customer', $customer->id)->sole();
        $this->assertSame(20000, $voucher->nilai_diskon);
        $this->assertSame($program->id_event, $voucher->id_event);
    }

    public function test_generate_requires_login_and_open_program(): void
    {
        $program = FundraiserProgram::factory()->create(['status' => false]);

        $this->postJson("/api/fundraiser/{$program->id}/kode")->assertUnauthorized();

        $this->asCustomer(Customer::factory()->create());
        $this->postJson("/api/fundraiser/{$program->id}/kode")->assertStatus(422);
        $this->assertDatabaseCount('kode_vouchers', 0);
    }

    public function test_checkout_with_fundraiser_code_applies_discount_and_records_commission(): void
    {
        $this->fakeMidtrans();
        $program = FundraiserProgram::factory()->create();
        $event = $program->event;
        $event->update(['jumlah_tiket' => 10]);
        $kode = $program->kodeForCustomer(Customer::factory()->create(['email' => 'fundraiser@example.test']));

        $this->checkout($event, $this->midtransPayment(), $kode->kode, tiket: 2)->assertOk();

        $transaksi = Transaksi::sole();
        $this->assertSame((200000 - 20000) * 2, $transaksi->total_pembayaran);
        $this->assertSame(15000 * 2, $transaksi->komisi_fundraiser);
        $this->assertSame(2, $kode->fresh()->digunakan);
    }

    public function test_checkout_stores_utm_from_fundraiser_link(): void
    {
        $this->fakeMidtrans();
        $program = FundraiserProgram::factory()->create();
        $program->event->update(['jumlah_tiket' => 10]);
        $kode = $program->kodeForCustomer(Customer::factory()->create());

        $this->checkout($program->event, $this->midtransPayment(), $kode->kode, extra: [
            'utm' => ['last' => ['params' => [
                'utm_source' => 'fundraiser',
                'utm_medium' => 'referral',
                'utm_campaign' => $program->event->slug,
                'utm_content' => $kode->kode,
            ]]],
        ])->assertOk();

        $transaksi = Transaksi::sole();
        $this->assertSame('fundraiser', $transaksi->utm_source);
        $this->assertSame($program->event->slug, $transaksi->utm_campaign);
        $this->assertSame($kode->kode, $transaksi->utm_data['last']['params']['utm_content']);
    }

    public function test_share_url_carries_utm(): void
    {
        $program = FundraiserProgram::factory()->create();
        $customer = Customer::factory()->create();

        $url = $this->asCustomer($customer)->postJson("/api/fundraiser/{$program->id}/kode")->json('data.kode.share_url');

        $this->assertStringContainsString('utm_source=fundraiser', $url);
        $this->assertStringContainsString('utm_medium=referral', $url);
    }

    public function test_fundraiser_cannot_use_own_code(): void
    {
        $this->fakeMidtrans();
        $program = FundraiserProgram::factory()->create();
        $program->event->update(['jumlah_tiket' => 10]);
        $owner = Customer::factory()->create(['email' => 'budi@gmail.com']);
        $kode = $program->kodeForCustomer($owner);

        $this->checkout($program->event, $this->midtransPayment(), $kode->kode, 'BUDI@gmail.com')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Kode fundraiser tidak bisa dipakai oleh pemilik kodenya sendiri.');

        $this->assertDatabaseCount('transaksis', 0);
        $this->assertSame(0, $kode->fresh()->digunakan);
        $this->assertSame(10, $program->event->fresh()->jumlah_tiket);
    }

    public function test_dashboard_counts_only_successful_commission_as_earned(): void
    {
        $program = FundraiserProgram::factory()->create();
        $customer = Customer::factory()->create();
        $kode = $program->kodeForCustomer($customer);

        foreach ([['Success', 2], ['Pending', 1], ['Failed', 1]] as [$status, $tiket]) {
            Transaksi::factory()->create([
                'id_event' => $program->id_event,
                'id_voucher' => $kode->id,
                'jumlah_tiket' => $tiket,
                'komisi_fundraiser' => 15000 * $tiket,
                'status_pembayaran' => $status,
            ]);
        }

        $this->asCustomer($customer)->getJson('/api/fundraiser')
            ->assertOk()
            ->assertJsonPath('summary.tiket_terjual', 2)
            ->assertJsonPath('summary.komisi_didapat', 30000)
            ->assertJsonPath('summary.komisi_pending', 15000)
            ->assertJsonPath('data.0.kode.kode', $kode->kode);
    }

    public function test_admin_program_update_syncs_existing_codes(): void
    {
        $program = FundraiserProgram::factory()->create();
        $kode = $program->kodeForCustomer(Customer::factory()->create());

        $this->actingAs(User::factory()->create())
            ->put(route('fundraiser.update', $program), [
                'id_event' => $program->id_event,
                'nilai_diskon' => 30000,
                'nilai_komisi' => 10000,
                'kuota_per_kode' => 25,
                'tanggal_berakhir' => now()->addDays(10)->toDateString(),
                'status' => 0,
            ])
            ->assertRedirect(route('fundraiser.show', $program));

        $kode->refresh();
        $this->assertSame(30000, $kode->nilai_diskon);
        $this->assertSame(25, $kode->kuota);
        $this->assertFalse($kode->status);
    }

    public function test_fundraiser_code_is_managed_from_program_not_voucher_menu(): void
    {
        $program = FundraiserProgram::factory()->create();
        $kode = $program->kodeForCustomer(Customer::factory()->create());
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('voucher.edit', $kode))
            ->assertRedirect(route('fundraiser.show', $program));

        $this->actingAs($admin)
            ->delete(route('voucher.destroy', $kode))
            ->assertRedirect(route('fundraiser.show', $program));

        $this->assertNotSoftDeleted($kode);
    }

    public function test_admin_cannot_set_discount_above_ticket_price(): void
    {
        $event = Event::factory()->create(['harga' => 50000]);

        $this->actingAs(User::factory()->create())
            ->post(route('fundraiser.store'), [
                'id_event' => $event->id,
                'nilai_diskon' => 60000,
                'nilai_komisi' => 5000,
                'kuota_per_kode' => 10,
                'tanggal_berakhir' => now()->addDays(10)->toDateString(),
            ])
            ->assertSessionHasErrors('nilai_diskon');

        $this->assertDatabaseCount('fundraiser_programs', 0);
    }
}
