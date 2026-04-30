<?php

namespace App\Jobs;

use App\Events\OltPortDataFetched;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Services\Network\ZteOltProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchOltPortDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $olt;
    protected $port;

    /**
     * Create a new job instance.
     */
    public function __construct(Olt $olt, OltPonPort $port)
    {
        $this->olt = $olt;
        $this->port = $port;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $shelf = $this->port->shelf ?: 1;
            $slot = $this->port->slot;
            $pon_port = $this->port->pon_port;

            Log::info("Starting background fetch for OLT: {$this->olt->name}, Port: {$shelf}/{$slot}/{$pon_port}");
            
            $service = new ZteOltProvisioningService($this->olt);
            $onus = $service->getOnusOnPortViaCli($shelf, $slot, $pon_port);
            
            // Store result in cache for polling (expires in 10 minutes)
            \Illuminate\Support\Facades\Cache::put("olt_port_data_{$this->port->id}", $onus, now()->addMinutes(10));
            
            // Sync Port Status to Database
            $newStatus = count($onus) > 0 ? 'active' : 'inactive';
            $this->port->update(['status' => $newStatus]);

            // UPDATE NOC DATA (CustomerSignalCache)
            foreach ($onus as $onuData) {
                try {
                    $sn = $onuData['sn'];
                    $customer = \App\Models\Customer::where('olt_id', $this->olt->id)
                        ->where('onu_sn', $sn)
                        ->first();

                    if ($customer) {
                        \App\Models\CustomerSignalCache::updateOrCreate(
                            ['customer_id' => $customer->id],
                            [
                                'onu_index' => $onuData['index'],
                                'rx_power' => is_numeric($onuData['signal']) ? $onuData['signal'] : null,
                                'status' => $onuData['status'],
                                'last_polled_at' => now(),
                            ]
                        );

                        // If status is online, also update the main customer table if needed
                        if ($onuData['status'] === 'online' && $customer->onu_index !== $onuData['index']) {
                            $customer->update(['onu_index' => $onuData['index']]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to sync NOC data for ONU {$onuData['sn']}: " . $e->getMessage());
                }
            }
            
            // Broadcast the result
            broadcast(new OltPortDataFetched($this->olt->id, $this->port->id, $onus));
            
            Log::info("Successfully fetched and broadcasted data for Port: {$this->port->id}. New Status: {$newStatus} (Synced " . count($onus) . " ONUs to NOC)");
        } catch (\Exception $e) {
            Log::error("Error in FetchOltPortDataJob: " . $e->getMessage());
        }
    }
}
