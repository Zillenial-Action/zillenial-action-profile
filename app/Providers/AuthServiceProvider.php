<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Customer;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Guard "customer" hanya membaca Bearer token milik Customer. Sengaja tidak
        // memakai guard "sanctum" karena guard itu mengecek session web (admin) lebih dulu.
        Auth::viaRequest('customer-token', function (Request $request): ?Customer {
            $plainToken = $request->bearerToken();

            if (! $plainToken) {
                return null;
            }

            $accessToken = PersonalAccessToken::findToken($plainToken);

            if (! $accessToken
                || ! $accessToken->tokenable instanceof Customer
                || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
                return null;
            }

            return $accessToken->tokenable->withAccessToken($accessToken);
        });
    }
}
