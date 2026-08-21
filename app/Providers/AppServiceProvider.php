<?php

namespace App\Providers;

use App\Mail\Transport\ResendTransport;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // laravel/telescope is a require-dev package (absent on production via
        // `composer install --no-dev`) — only register it where its classes actually
        // exist, so production never hard-fails trying to load a provider that's not
        // installed there.
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('rupiah', function ( $expression ) { return "Rp. <?php echo number_format($expression,0,',','.'); ?>"; });

        // Daftarkan transport email Resend (HTTPS API) — pengganti SMTP yang
        // menggantung di Railway. Aktif saat MAIL_MAILER=resend.
        Mail::extend('resend', function () {
            return new ResendTransport(new Client(), (string) config('services.resend.api_key'));
        });
    }
}
