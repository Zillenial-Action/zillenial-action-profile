<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerTransactionClaimService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class CustomerAuthController extends Controller
{
    public function __construct(private CustomerTransactionClaimService $claimService) {}

    public function register(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'confirmed', $this->strongPasswordRule()],
        ]);
        $data['email'] = $this->normalizeEmail($data['email']);

        $customer = Customer::create($data);
        Auth::guard('customer')->login($customer);
        $this->regenerateSession($request);

        event(new Registered($customer));

        return response()->json([
            'message' => 'Akun dibuat. Periksa email untuk verifikasi.',
            'data' => $this->customerPayload($customer),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $email = $this->normalizeEmail($data['email']);
        $remember = (bool) ($data['remember'] ?? false);
        $guard = Auth::guard('customer');
        $guard->setRememberDuration($remember ? 43200 : 120);

        if (! $guard->attempt(['email' => $email, 'password' => $data['password']], $remember)) {
            return $this->error('Email atau password tidak cocok.', 'INVALID_CREDENTIALS', 401);
        }

        $this->regenerateSession($request);
        /** @var Customer $customer */
        $customer = $guard->user();
        if ($customer->hasVerifiedEmail()) {
            $this->claimService->claim($customer);
        }

        return response()->json([
            'message' => $customer->hasVerifiedEmail()
                ? 'Login berhasil.'
                : 'Login berhasil. Verifikasi email untuk membuka akun dan transaksi.',
            'data' => $this->customerPayload($customer),
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        Auth::guard('customer')->logout();
        if ($request->hasSession()) {
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return $this->error('Sesi customer tidak ditemukan.', 'UNAUTHENTICATED', 401);
        }

        return response()->json(['data' => $this->customerPayload($customer)]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $this->validated($request, ['email' => ['required', 'email']]);
        $email = $this->normalizeEmail($data['email']);

        Password::broker('customers')->sendResetLink(['email' => $email]);

        return response()->json([
            'message' => 'Jika email terdaftar, instruksi reset password akan dikirim.',
            'code' => 'PASSWORD_RESET_REQUEST_ACCEPTED',
            'errors' => [],
        ], 202);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', $this->strongPasswordRule()],
        ]);
        $data['email'] = $this->normalizeEmail($data['email']);

        $status = Password::broker('customers')->reset(
            $data,
            function (Customer $customer) use ($data): void {
                $customer->forceFill([
                    'password' => $data['password'],
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error('Token reset password tidak valid atau sudah kedaluwarsa.', 'RESET_TOKEN_INVALID', 422);
        }

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan login kembali.',
            'code' => 'PASSWORD_RESET',
            'errors' => [],
        ]);
    }

    public function sendVerificationNotification(Request $request): JsonResponse
    {
        /** @var Customer|null $customer */
        $customer = $request->user('customer');
        if (! $customer) {
            return $this->error('Sesi customer tidak ditemukan.', 'UNAUTHENTICATED', 401);
        }
        if ($customer->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah terverifikasi.', 'code' => 'EMAIL_ALREADY_VERIFIED', 'errors' => []]);
        }

        $customer->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Email verifikasi dikirim ulang.',
            'code' => 'VERIFICATION_SENT',
            'errors' => [],
        ], 202);
    }

    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        abort_unless(hash_equals(sha1($customer->getEmailForVerification()), $hash), 403);

        if (! $customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
            $this->claimService->claim($customer);
        }

        Auth::guard('customer')->login($customer);
        $this->regenerateSession($request);

        return $this->frontendRedirect('/auth/callback?email_verified=1');
    }

    public function googleRedirect(): RedirectResponse
    {
        if (! $this->googleAuthConfigured()) {
            return $this->frontendRedirect('/auth/callback?oauth=disabled');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        if (! $this->googleAuthConfigured()) {
            return $this->frontendRedirect('/auth/callback?oauth=disabled');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $rawUser = is_array($googleUser->user ?? null) ? $googleUser->user : [];
            $email = $this->normalizeEmail((string) $googleUser->getEmail());
            $emailVerified = filter_var($rawUser['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($email === '' || ! $emailVerified) {
                return $this->frontendRedirect('/auth/callback?oauth=email-not-verified');
            }

            $customer = Customer::where('google_id', $googleUser->getId())->first();
            if (! $customer) {
                $customer = Customer::where('email', $email)
                    ->whereNotNull('email_verified_at')
                    ->first();
            }
            if (! $customer) {
                $customer = Customer::create([
                    'name' => trim((string) $googleUser->getName()) ?: 'Customer Sostrip',
                    'email' => $email,
                    'password' => null,
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);
            } else {
                if ($customer->google_id && $customer->google_id !== $googleUser->getId()) {
                    return $this->frontendRedirect('/auth/callback?oauth=account-conflict');
                }
                $customer->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $customer->email_verified_at ?: now(),
                ])->save();
            }

            $this->claimService->claim($customer);
            $guard = Auth::guard('customer');
            $guard->setRememberDuration(43200);
            $guard->login($customer, true);
            $this->regenerateSession($request);

            return $this->frontendRedirect('/auth/callback?oauth=success');
        } catch (\Throwable $exception) {
            Log::warning('Google customer authentication failed', [
                'message' => $exception->getMessage(),
                'ip' => $request->ip(),
            ]);

            return $this->frontendRedirect('/auth/callback?oauth=failed');
        }
    }

    public function passwordResetRedirect(Request $request, string $token): RedirectResponse
    {
        $email = (string) $request->query('email', '');
        $query = http_build_query(['token' => $token, 'email' => $email]);

        return $this->frontendRedirect('/auth/reset-password?'.$query);
    }

    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'email_verified' => $customer->hasVerifiedEmail(),
            'initials' => collect(preg_split('/\s+/', trim($customer->name)) ?: [])
                ->filter()
                ->take(2)
                ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
                ->implode('') ?: 'SZ',
        ];
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * Aturan password kuat: panjang 15-72 karakter, ada huruf besar, huruf kecil,
     * angka, dan simbol. Ditulis sebagai closure (bukan rule string bawaan Laravel)
     * supaya pesan errornya bisa langsung dalam Bahasa Indonesia tanpa file lang tambahan.
     */
    private function strongPasswordRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || mb_strlen($value) < 15 || mb_strlen($value) > 72) {
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

    private function frontendRedirect(string $path): RedirectResponse
    {
        return redirect()->away(rtrim((string) config('app.frontend_url'), '/').$path);
    }

    private function googleAuthConfigured(): bool
    {
        return (bool) config('app.google_auth_enabled')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function error(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code, 'errors' => []], $status);
    }

    private function regenerateSession(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }

    private function validated(Request $request, array $rules): array
    {
        try {
            return $request->validate($rules);
        } catch (ValidationException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => 'Data yang dikirim belum valid.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $exception->errors(),
            ], 422));
        }
    }
}
