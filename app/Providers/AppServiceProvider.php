<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogAuthenticationActivity;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @canroute('packages.create') … @endcanroute — tampilkan tombol/tautan hanya bila rutenya boleh dibuka
        // pengguna ini (sumber yang sama dengan middleware role pada rute; lihat App\Support\RouteAccess).
        \Illuminate\Support\Facades\Blade::if('canroute', function (string ...$routes) {
            $user = auth()->user();
            if (! $user instanceof \App\Models\User) {
                return false;
            }
            foreach ($routes as $route) {
                if (! \App\Support\RouteAccess::allows($user, $route)) {
                    return false;
                }
            }

            return true;
        });

        // Limiter terpisah untuk verifikasi & kirim ulang OTP portal. `throttle:N,1` biasa memakai satu
        // penghitung per IP untuk semua rute, sehingga beberapa kali salah ketik kode memblokir "kirim ulang".
        \Illuminate\Support\Facades\RateLimiter::for('portal-otp-verify', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('verify|' . $request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('portal-otp-resend', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('resend|' . $request->ip()));

        // Hanya proxy yang benar-benar di depan aplikasi yang boleh menentukan IP klien lewat
        // X-Forwarded-For. Dulu '*' (siapa saja): batas percobaan login per IP bisa ditembus.
        $trusted = trim((string) config('app.trusted_proxies', '127.0.0.1,::1'));
        \Illuminate\Http\Middleware\TrustProxies::at($trusted === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $trusted)))));

        if (!app()->runningInConsole()) {
            if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https' || str_starts_with(config('app.url'), 'https://') || app()->isProduction()) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        \App\Models\Customer::observe(\App\Observers\CustomerObserver::class);
        \App\Models\Radius\Nas::observe(\App\Observers\NasObserver::class);
        
        Event::listen(
            [Login::class, Logout::class],
            LogAuthenticationActivity::class
        );
    }
}
