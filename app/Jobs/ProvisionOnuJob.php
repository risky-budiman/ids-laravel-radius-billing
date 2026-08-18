<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\Network\OltGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }

    public function handle()
    {
        $customer = Customer::with('olt')->find($this->customerId);
        if (!$customer || !$customer->olt || !$customer->onu_sn) {
            Log::warning("Provisioning Job: Missing data for customer ID {$this->customerId}");
            return;
        }

        Log::info("Starting background provisioning for customer: {$customer->name}");

        $gateway = new OltGateway($customer->olt);
        
        // Parse shelf/slot/port from onu_index (.1.1.7.1)
        $pos = $customer->onu_index; 
        $parts = explode('.', trim($pos, '.'));
        
        if (count($parts) < 3) {
            Log::error("Invalid ONU index format for provisioning: {$pos}");
            return;
        }

        $shelf = $parts[0];
        $slot = $parts[1];
        $port = $parts[2];

        // Default VLAN 100
        $vlan = 100;
        
        $result = $gateway->provisionOnu(
            $shelf, 
            $slot, 
            $port, 
            $customer->onu_sn, 
            $customer->onu_type ?: 'ZTE-ONU',
            $vlan,
            $customer->name
        );

        if ($result) {
            $customer->update(['onu_index' => $result]);
            Log::info("Provisioning successful for {$customer->name}: New Index is {$result}");
        } else {
            Log::error("Provisioning failed for {$customer->name}");
        }
    }
}
