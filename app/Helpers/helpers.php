<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('is_accounting_locked')) {
    /**
     * Check if the given date is in a locked accounting period
     */
    function is_accounting_locked($date)
    {
        $closedUntil = get_setting('accounting_closed_until');
        if (!$closedUntil) return false;
        
        try {
            $checkDate = \Carbon\Carbon::parse($date);
            $lockDate = \Carbon\Carbon::parse($closedUntil);
            return $checkDate->lte($lockDate);
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('get_setting')) {
    /**
     * Get a setting value by its key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get_setting($key, $default = null)
    {
        // Use cache to prevent multiple DB queries per request
        $settings = Cache::rememberForever('app_settings', function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }
}
