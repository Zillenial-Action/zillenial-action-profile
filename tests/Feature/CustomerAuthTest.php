<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Sostrip-Rahasia-2026!';

    private function register(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/auth/register', array_merge([
            'name' => 'Peserta Sostrip',
            'email' => 'Peserta@Example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides));
    }

    private function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    /**
     * Guard request di-cache per aplikasi; reset agar request berikutnya
     * membaca token lagi seperti request baru dari browser.
     */
    private function freshRequest(): void
    {
        $this->app['auth']->forgetGuards();
    }

    public function test_register_creates_customer_and_returns_bearer_token(): void
    {
        $response = $this->register();

        $response->assertCreated()
            ->assertJsonPath('data.email', 'peserta@example.test')
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('customers', ['email' => 'peserta@example.test', 'google_id' => null]);

        $this->freshRequest();
        $this->getJson('/api/auth/me', $this->bearer($response->json('token')))
            ->assertOk()
            ->assertJsonPath('data.name', 'Peserta Sostrip');
    }

    public function test_register_rejects_weak_password_and_duplicate_email(): void
    {
        $this->register(['password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->register()->assertCreated();

        $this->register(['email' => 'peserta@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_with_email_and_password(): void
    {
        $this->register()->assertCreated();

        $this->postJson('/api/auth/login', ['email' => 'peserta@example.test', 'password' => 'salah-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/auth/login', ['email' => 'PESERTA@example.test', 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonStructure(['token', 'data' => ['id', 'name', 'email', 'email_verified', 'has_google', 'initials']]);
    }

    public function test_google_only_account_cannot_login_with_password(): void
    {
        Customer::create([
            'name' => 'Akun Google',
            'email' => 'google@example.test',
            'google_id' => 'google-123',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/auth/login', ['email' => 'google@example.test', 'password' => self::PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $token = $this->register()->json('token');

        $this->freshRequest();
        $this->postJson('/api/auth/logout', [], $this->bearer($token))->assertOk();

        $this->freshRequest();
        $this->getJson('/api/auth/me', $this->bearer($token))->assertUnauthorized();
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = $this->register()->json('token');
        Customer::first()->tokens()->update(['expires_at' => now()->subMinute()]);

        $this->freshRequest();
        $this->getJson('/api/auth/me', $this->bearer($token))->assertUnauthorized();
    }

    public function test_admin_session_and_admin_token_are_not_customer_sessions(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->getJson('/api/auth/me')->assertUnauthorized();

        $this->freshRequest();
        $adminToken = $admin->createToken('admin')->plainTextToken;
        $this->getJson('/api/auth/me', $this->bearer($adminToken))->assertUnauthorized();
    }

    public function test_customer_token_does_not_open_admin_panel_and_admin_login_still_works(): void
    {
        $token = $this->register()->json('token');

        $this->freshRequest();
        $this->get('/dashboard', $this->bearer($token))->assertRedirect(route('login'));

        $admin = User::factory()->create(['password' => 'admin-password']);
        $this->post('/auth', ['email' => $admin->email, 'password' => 'admin-password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->get('/dashboard')->assertOk();
    }

    public function test_orders_only_show_email_matches_after_email_is_proven(): void
    {
        $event = Event::factory()->create(['status' => true]);
        $payment = Payment::factory()->create(['status' => true, 'type' => 'midtrans']);
        $makeOrder = fn (string $invoice, array $attributes = []) => Transaksi::create(array_merge([
            'id_event' => $event->id,
            'invoice' => $invoice,
            'jumlah_tiket' => 1,
            'total_pembayaran' => 150000,
            'name' => 'Peserta',
            'email' => 'peserta@example.test',
            'telepon' => '+6281234567890',
            'status_pembayaran' => 'Success',
            'tanggal_register' => now(),
            'id_payment' => $payment->id,
        ], $attributes));

        $token = $this->register()->json('token');
        $customer = Customer::first();

        $makeOrder('INV-GUEST-SAME-EMAIL');
        $makeOrder('INV-OWN', ['id_customer' => $customer->id, 'email' => 'teman@example.test']);
        $makeOrder('INV-OTHER', ['email' => 'orang-lain@example.test']);

        $this->freshRequest();
        $this->getJson('/api/account/orders', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice', 'INV-OWN')
            ->assertJsonPath('data.0.action.type', 'view_ticket');

        $customer->forceFill(['email_verified_at' => now()])->save();

        $this->freshRequest();
        $invoices = collect($this->getJson('/api/account/orders', $this->bearer($token))->json('data'))->pluck('invoice');
        $this->assertEqualsCanonicalizing(['INV-GUEST-SAME-EMAIL', 'INV-OWN'], $invoices->all());
    }

    public function test_orders_require_customer_token(): void
    {
        $this->getJson('/api/account/orders')->assertUnauthorized();
    }

    public function test_checkout_links_order_to_logged_in_customer_and_guest_checkout_still_works(): void
    {
        $this->app->instance(MidtransService::class, new class extends MidtransService
        {
            public function __construct() {}

            public function charge(Transaksi $transaksi, Payment $payment): array
            {
                return [
                    'bank' => 'bca',
                    'va_number' => '12345678901',
                    'expiry_time' => now()->addDay()->format('Y-m-d H:i:s'),
                ];
            }
        });

        Event::factory()->create(['slug' => 'social-trip', 'status' => true, 'jumlah_tiket' => 10]);
        $payment = Payment::factory()->create([
            'status' => true,
            'type' => 'midtrans',
            'midtrans_payment_type' => 'bank_transfer',
            'midtrans_bank' => 'bca',
        ]);
        $payload = [
            'event_slug' => 'social-trip',
            'jumlah_tiket' => 1,
            'payment_method_id' => $payment->id,
            'pengunjung' => [
                ['name' => 'Budi Santoso', 'telepon' => '81234567890', 'email' => 'budi@gmail.com'],
            ],
        ];

        $guest = $this->postJson('/api/checkout', $payload)->assertOk();
        $this->assertDatabaseHas('transaksis', ['invoice' => $guest->json('order_id'), 'id_customer' => null]);

        $token = $this->register()->json('token');

        $this->freshRequest();
        $member = $this->postJson('/api/checkout', $payload, $this->bearer($token))->assertOk();
        $this->assertDatabaseHas('transaksis', [
            'invoice' => $member->json('order_id'),
            'id_customer' => Customer::first()->id,
        ]);
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.customer_auth.frontend_url' => 'https://sostrip.test',
        ]);
    }

    private function fakeGoogleUser(string $id, string $email, bool $verified = true): void
    {
        $googleUser = (new GoogleUser)
            ->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Pengguna Google', 'email' => $email]);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function codeFromCallback(\Illuminate\Testing\TestResponse $response): string
    {
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://sostrip.test/auth/callback#', $location);
        parse_str((string) parse_url($location, PHP_URL_FRAGMENT), $fragment);

        return (string) ($fragment['code'] ?? '');
    }

    public function test_google_redirect_falls_back_when_not_configured(): void
    {
        config(['services.google.client_id' => null, 'services.customer_auth.frontend_url' => 'https://sostrip.test']);

        $this->get('/auth/google/redirect')
            ->assertRedirect('https://sostrip.test/auth/callback#error=google_disabled');
    }

    public function test_google_login_creates_verified_customer_and_code_is_single_use(): void
    {
        $this->configureGoogle();
        $this->fakeGoogleUser('google-abc', 'Baru@Gmail.com');

        $code = $this->codeFromCallback($this->get('/auth/google/callback?code=x&state=y'));
        $this->assertSame(64, strlen($code));

        $this->postJson('/api/auth/google/exchange', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.email', 'baru@gmail.com')
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('data.has_google', true);

        $this->postJson('/api/auth/google/exchange', ['code' => $code])->assertUnprocessable();
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_google_login_takes_over_unproven_password_account_safely(): void
    {
        $this->configureGoogle();
        $oldToken = $this->register()->json('token');
        $this->fakeGoogleUser('google-xyz', 'peserta@example.test');

        $code = $this->codeFromCallback($this->get('/auth/google/callback?code=x&state=y'));
        $this->postJson('/api/auth/google/exchange', ['code' => $code])->assertOk();

        $customer = Customer::first();
        $this->assertSame('google-xyz', $customer->google_id);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertNull($customer->password);

        $this->freshRequest();
        $this->getJson('/api/auth/me', $this->bearer($oldToken))->assertUnauthorized();
    }

    public function test_google_login_rejects_unverified_google_email(): void
    {
        $this->configureGoogle();
        $this->fakeGoogleUser('google-unverified', 'belum@gmail.com', verified: false);

        $this->get('/auth/google/callback?code=x&state=y')
            ->assertRedirect('https://sostrip.test/auth/callback#error=google_email_unverified');
        $this->assertDatabaseCount('customers', 0);
    }
}
