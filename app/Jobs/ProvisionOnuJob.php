<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\Network\ZteOltProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;

    /**
     * Create a new job instance.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customer = $this->customer;
        
        if (!$customer->olt_id || !$customer->onu_index) {
            Log::error("Provisioning failed: Customer {$customer->id} has no OLT or ONU index assigned.");
            return;
        }

        $olt = $customer->olt;
        $provisioning = new ZteOltProvisioningService($olt);

        // Parse index .shelf.slot.port.onu_id
        $parts = explode('.', ltrim($customer->onu_index, '.'));
        if (count($parts) < 4) {
            Log::error("Provisioning failed: Invalid ONU index format for customer {$customer->id}.");
            return;
        }

        $shelf = $parts[0];
        $slot = $parts[1];
        $port = $parts[2];
        $onuId = $parts[3];

        // VLAN from regional/STO settings or static for now
        $vlan = 100; // Placeholder
        $bandwidth = $customer->package->speed_limit_down ?? 102400; // kbps

        Log::info("Starting OLT Provisioning for Customer: {$customer->name} (SN: {$customer->onu_sn})");

        $success = $provisioning->registerOnu(
            $shelf, $slot, $port, $onuId, 
            $customer->onu_sn, 
            $customer->onu_type ?? 'F660', 
            $vlan, 
            $bandwidth
        );

        if ($success) {
            $customer->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);
            Log::info("Provisioning successful for Customer: {$customer->name}");
        } else {
            Log::error("Provisioning FAILED for Customer: {$customer->name}");
        }
    }
}
