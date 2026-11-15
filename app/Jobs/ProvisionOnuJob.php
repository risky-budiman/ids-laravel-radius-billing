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
        $customer = Customer::with('olt', 'package')->find($this->customerId);
        if (!$customer || !$customer->olt || !$customer->onu_sn) {
            Log::warning("Provisioning Job: Missing data for customer ID {$this->customerId}");
            return;
        }

        Log::info("Starting background provisioning for customer: {$customer->name}");

        $gateway = new OltGateway($customer->olt);
        
        // Port PON dari onu_index: ".1.1.7", "1.1.7.5", "1/1/7" atau "1/1/7:5" (dulu hanya format titik)
        $pos = (string) $customer->onu_index;
        $parts = preg_split('/[.\/:]/', trim($pos, '.'), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) < 3 || array_filter(array_slice($parts, 0, 3), fn ($p) => ! ctype_digit($p))) {
            Log::error("Invalid ONU index format for provisioning: {$pos}");
            return;
        }

        [$shelf, $slot, $port] = array_map('intval', array_slice($parts, 0, 3));

        // VLAN dari isian aktivasi (dulu selalu 100); profil T-CONT dari paket (dulu selalu UP-100M)
        $vlan = (int) ($customer->vlan_id ?: 100);

        $result = $gateway->provisionOnu(
            $shelf,
            $slot,
            $port,
            $customer->onu_sn,
            $customer->onu_type ?: 'ZTE-ONU',
            $vlan,
            $customer->customer_code ?: $customer->name,
            $customer->package?->olt_tcont_profile
        );

        if ($result) {
            $customer->update(['onu_index' => $result]);
            Log::info("Provisioning successful for {$customer->name}: New Index is {$result}");
        } else {
            Log::error("Provisioning failed for {$customer->name}");
        }
    }
}
