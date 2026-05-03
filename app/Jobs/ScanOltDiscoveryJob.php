<?php

namespace App\Jobs;

use App\Models\Olt;
use App\Services\Network\SnmpService;
use App\Services\Network\OltDiscoveryService;
use App\Services\Network\ZteOltProvisioningService;
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
        Log::info("Background Discovery Job Started");
        
        try {
            $activeOlts = Olt::where('is_active', true)->get();
            $discoveredOnus = [];

            foreach ($activeOlts as $olt) {
                try {
                    Log::info("Background Scanning OLT: {$olt->name}");
                    
                    $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
                    
                    // Test connection first
                    $test = $snmp->testConnection();
                    if (!$test['status']) {
                        Log::warning("SNMP not responding for {$olt->name}, skipping SNMP discovery.");
                        $onus = [];
                    } else {
                        $discovery = new OltDiscoveryService($snmp);
                        $onus = $discovery->scanUnconfiguredOnus();
                    }
                    
                    // Fallback to Telnet if SNMP found nothing or failed
                    if (empty($onus)) {
                        Log::info("SNMP found no ONUs, trying Telnet fallback for {$olt->name}");
                        $provisioningService = new ZteOltProvisioningService($olt);
                        $onus = $provisioningService->getUnconfiguredOnus();
                    }

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
            Cache::put('noc_discovery_last_run', now()->toDateTimeString(), 3600); // Store as string to avoid serialization issues
            
            Log::info("Background Discovery Job Finished. Found " . count($discoveredOnus) . " ONUs.");
        } finally {
            Cache::forget('noc_discovery_running');
        }
    }
}
