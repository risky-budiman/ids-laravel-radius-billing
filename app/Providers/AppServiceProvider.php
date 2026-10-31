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
