<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncOltSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    public function handle()
    {
        Log::info("Manual Signal Sync Started");
        Cache::put('noc_signals_running', true, 600);
        
        try {
            // Re-use the existing command logic
            Artisan::call('noc:monitor');
            Log::info("Manual Signal Sync Finished via noc:monitor");
        } catch (\Exception $e) {
            Log::error("Manual Signal Sync Failed: " . $e->getMessage());
        } finally {
            Cache::forget('noc_signals_running');
        }
    }
}
