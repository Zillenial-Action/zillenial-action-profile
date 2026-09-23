<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_requires_a_long_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Peserta Sostrip',
            'email' => 'peserta@example.test',
            'password' => 'terlalu-pendek',
            'password_confirmation' => 'terlalu-pendek',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_password_complexity(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Peserta Sostrip',
            'email' => 'peserta@example.test',
            'password' => 'semuanya-huruf-kecil-saja-panjang',
            'password_confirmation' => 'semuanya-huruf-kecil-saja-panjang',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_logs_customer_in_and_sends_verification(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Peserta Sostrip',
            'email' => 'Peserta@Example.test',
            'password' => 'Password-Panjang-Yang-Aman123!',
            'password_confirmation' => 'Password-Panjang-Yang-Aman123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'peserta@example.test')
            ->assertJsonPath('data.email_verified', false);

        $this->assertAuthenticatedAs(Customer::first(), 'customer');
        Mail::assertSentCount(1);
    }

    public function test_guest_cannot_read_customer_orders(): void
    {
        $this->getJson('/api/account/orders')->assertUnauthorized();
    }

    public function test_guest_cannot_read_customer_profile(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_unverified_customer_cannot_read_customer_orders(): void
    {
        $customer = Customer::create([
            'name' => 'Peserta Belum Verifikasi',
            'email' => 'belum-verifikasi@example.test',
            'password' => 'password-panjang-yang-aman',
        ]);

        $this->actingAs($customer, 'customer');

        $this->getJson('/api/account/orders')
            ->assertForbidden()
            ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_customer_session_does_not_authenticate_as_admin(): void
    {
        $customer = Customer::create([
            'name' => 'Peserta Sostrip',
            'email' => 'peserta@example.test',
            'password' => 'password-panjang-yang-aman',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer, 'customer');

        $this->get('/dashboard')->assertRedirect('/login');
    }
}
