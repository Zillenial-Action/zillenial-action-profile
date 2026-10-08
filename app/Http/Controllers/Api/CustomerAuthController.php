<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

/**
 * Login customer portal Sostrip: email/password dan Google OAuth2.
 *
 * Sesi berupa Bearer token Sanctum, sehingga tidak memakai session, cookie,
 * maupun CSRF milik login admin.
 */
class CustomerAuthController extends Controller
{
    private const OAUTH_CODE_TTL_SECONDS = 120;

    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => $this->normalizeEmail((string) $request->input('email'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'confirmed', $this->strongPasswordRule()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama lengkap minimal 2 karakter.',
            'name.max' => 'Nama lengkap maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Ulangi password harus sama.',
        ]);

        $customer = Customer::create([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return response()->json([
            'message' => 'Akun berhasil dibuat.',
            'token' => $this->issueToken($customer),
            'data' => $this->customerPayload($customer),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $customer = Customer::where('email', $this->normalizeEmail($data['email']))->first();

        // Akun Google tanpa password juga jatuh ke pesan yang sama supaya
        // keberadaan email tidak bisa ditebak dari respons login.
        if (! $customer || ! $customer->password || ! Hash::check($data['password'], $customer->password)) {
            Log::warning('Failed customer login attempt', [
                'email' => $this->normalizeEmail($data['email']),
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        return response()->json([
            'message' => 'Berhasil masuk.',
            'token' => $this->issueToken($customer),
            'data' => $this->customerPayload($customer),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->customerPayload($request->user('customer'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('customer')->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function googleRedirect(): RedirectResponse
    {
        if (! $this->googleConfigured()) {
            return $this->frontendCallback(['error' => 'google_disabled']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        if (! $this->googleConfigured()) {
            return $this->frontendCallback(['error' => 'google_disabled']);
        }

        if ($request->filled('error')) {
            return $this->frontendCallback(['error' => 'google_cancelled']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google customer login failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return $this->frontendCallback(['error' => 'google_failed']);
        }

        $googleId = (string) $googleUser->getId();
        $email = $this->normalizeEmail((string) $googleUser->getEmail());
        $raw = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        $emailVerified = filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($googleId === '' || $email === '' || ! $emailVerified) {
            return $this->frontendCallback(['error' => 'google_email_unverified']);
        }

        $customer = Customer::where('google_id', $googleId)->first()
            ?? Customer::where('email', $email)->first();

        if (! $customer) {
            $customer = Customer::create([
                'name' => trim((string) $googleUser->getName()) ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
            ]);
        } elseif ($customer->google_id === null) {
            // Google membuktikan email ini milik pemilik akun Google. Kalau akun lama dibuat
            // dengan password tanpa bukti kepemilikan email, password dan token lamanya dicabut
            // agar orang yang mendaftarkan email milik orang lain tidak ikut memegang akun ini.
            if (! $customer->hasVerifiedEmail()) {
                $customer->tokens()->delete();
                $customer->password = null;
            }

            $customer->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $customer->email_verified_at ?? now(),
            ])->save();
        } elseif ($customer->google_id !== $googleId) {
            return $this->frontendCallback(['error' => 'google_account_conflict']);
        }

        $code = Str::random(64);
        Cache::put($this->oauthCacheKey($code), $customer->id, self::OAUTH_CODE_TTL_SECONDS);

        return $this->frontendCallback(['code' => $code]);
    }

    /**
     * Tukar kode sekali pakai dari callback Google menjadi Bearer token.
     * Token tidak pernah ditaruh di URL redirect.
     */
    public function googleExchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        $customerId = Cache::pull($this->oauthCacheKey($data['code']));
        $customer = $customerId ? Customer::find($customerId) : null;

        if (! $customer) {
            return response()->json([
                'message' => 'Kode login Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.',
            ], 422);
        }

        return response()->json([
            'message' => 'Berhasil masuk.',
            'token' => $this->issueToken($customer),
            'data' => $this->customerPayload($customer),
        ]);
    }

    private function issueToken(Customer $customer): string
    {
        $customer->tokens()->where('expires_at', '<', now())->delete();

        $ttlDays = max(1, (int) config('services.customer_auth.token_ttl_days', 30));

        return $customer->createToken('sostrip', ['*'], now()->addDays($ttlDays))->plainTextToken;
    }

    private function customerPayload(Customer $customer): array
    {
        $initials = collect(preg_split('/\s+/', trim($customer->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'email_verified' => $customer->hasVerifiedEmail(),
            'has_google' => $customer->google_id !== null,
            'initials' => $initials ?: 'ZA',
        ];
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * 15-72 karakter (batas bcrypt), huruf besar dan kecil, angka, dan simbol.
     * Aturan yang sama dicek di frontend untuk umpan balik instan.
     */
    private function strongPasswordRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || mb_strlen($value) < 15 || strlen($value) > 72) {
                $fail('Password harus 15 sampai 72 karakter.');

                return;
            }

            if (! preg_match('/[a-z]/', $value) || ! preg_match('/[A-Z]/', $value)) {
                $fail('Password harus mengandung huruf besar dan huruf kecil.');
            }

            if (! preg_match('/\d/', $value)) {
                $fail('Password harus mengandung angka.');
            }

            if (! preg_match('/[^A-Za-z0-9]/', $value)) {
                $fail('Password harus mengandung simbol, misalnya ! # $ % &.');
            }
        };
    }

    private function googleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function oauthCacheKey(string $code): string
    {
        return 'customer-oauth-code:'.hash('sha256', $code);
    }

    /**
     * Hasil OAuth dikirim lewat fragment (#) agar tidak ikut tercatat di log server
     * maupun header Referer.
     */
    private function frontendCallback(array $params): RedirectResponse
    {
        $base = rtrim((string) config('services.customer_auth.frontend_url'), '/');

        return redirect()->away($base.'/auth/callback#'.http_build_query($params));
    }
}
