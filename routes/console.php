<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('app:process-billing')->dailyAt('00:01');
Schedule::command('customer:check-grace-period')->everyMinute();

// FUP & Quota Management
Schedule::command('app:sync-usage')->everyFiveMinutes();
Schedule::command('app:reset-usage')->monthlyOn(1, '00:00');
