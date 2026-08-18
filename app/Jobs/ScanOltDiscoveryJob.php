<?php

namespace App\Jobs;

use App\Models\Olt;
use App\Services\Network\OltGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScanOltDiscoveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public function handle()
    {
        Log::info("Background Discovery Job Started (Pure SNMP)");
        
        try {
            $activeOlts = Olt::where('is_active', true)->get();
            $discoveredOnus = [];

            foreach ($activeOlts as $olt) {
                try {
                    Log::info("SNMP Scanning OLT: {$olt->name}");
                    
                    $gateway = new OltGateway($olt);
                    
                    // Test connection first
                    $test = $gateway->testSnmpConnection();
                    if (!$test['status']) {
                        Log::warning("SNMP not responding for {$olt->name}, skipping.");
                        continue;
                    }

                    $onus = $gateway->scanUnconfiguredOnus();

                    foreach ($onus as $onu) {
                        $onu['olt_name'] = $olt->name;
                        $onu['olt_id'] = $olt->id;
                        $discoveredOnus[] = $onu;
                    }
                } catch (\Exception $e) {
                    Log::warning("Discovery failed for OLT {$olt->name}: " . $e->getMessage());
                }
            }

            // Store results in cache
            Cache::put('noc_discovered_onus', $discoveredOnus, 3600);
            Cache::put('noc_discovery_last_run', now()->toDateTimeString(), 3600);
            
            Log::info("Background Discovery Job Finished. Found " . count($discoveredOnus) . " ONUs via SNMP.");
        } finally {
            Cache::forget('noc_discovery_running');
        }
    }
}
