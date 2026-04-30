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
            
            // Broadcast the result
            broadcast(new OltPortDataFetched($this->olt->id, $this->port->id, $onus));
            
            Log::info("Successfully fetched and broadcasted data for Port: {$this->port->id}. New Status: {$newStatus}");
        } catch (\Exception $e) {
            Log::error("Error in FetchOltPortDataJob: " . $e->getMessage());
        }
    }
}
