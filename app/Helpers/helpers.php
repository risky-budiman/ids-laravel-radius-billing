<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('is_accounting_locked')) {
    /**
     * Check if the given date is in a locked accounting period
     */
    function is_accounting_locked($date)
    {
        if (isset($GLOBALS['bypass_accounting_lock']) && $GLOBALS['bypass_accounting_lock'] === true) {
            return false;
        }

        static $cachedPeriods = [];
        
        try {
            $checkDate = \Carbon\Carbon::parse($date);
            $key = $checkDate->format('Y-m');
            
            if (!array_key_exists($key, $cachedPeriods)) {
                $period = \App\Models\AccountingPeriod::where('month', $checkDate->month)
                    ->where('year', $checkDate->year)
                    ->first();
                
                $cachedPeriods[$key] = $period ? (bool)$period->is_closed : false;
            }
            
            return $cachedPeriods[$key];
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
if (!function_exists('app_version')) {
    /**
     * Get the current application version.
     */
    function app_version()
    {
        $versionFile = base_path('VERSION');
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        return 'v1.0.0';
    }
}
