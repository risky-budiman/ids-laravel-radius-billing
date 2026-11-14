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
