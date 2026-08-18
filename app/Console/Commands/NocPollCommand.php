<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Olt;
use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\CustomerSignalLog;
use App\Services\Network\OltGateway;
use Illuminate\Support\Facades\Log;

class NocPollCommand extends Command
{
    protected $signature = 'noc:monitor';
    protected $description = 'Poll OLTs for ONU status and signal power via SNMP';

    public function handle()
    {
        $this->info('Starting NOC SNMP Polling...');
        Log::info("NOC Poll: Starting execution...");
        
        $olts = Olt::where('is_active', true)->get();
        Log::info("NOC Poll: Found " . $olts->count() . " active OLTs.");

        if ($olts->isEmpty()) {
            Log::warning("NOC Poll: No active OLTs found to poll.");
            return;
        }

        foreach ($olts as $olt) {
            $this->info("Polling OLT: {$olt->name} ({$olt->ip_address})...");
            
            try {
                $gateway = new OltGateway($olt);

                // Bulk walk all ONU data in one shot (efficient)
                $bulkData = $gateway->bulkWalkAllOnus();
                $this->info("SNMP Bulk Walk: SNs=" . count($bulkData['sns']) . 
                    ", Statuses=" . count($bulkData['statuses']) . 
                    ", Signals=" . count($bulkData['signals']));

                $customers = Customer::where('olt_id', $olt->id)->get();
                Log::info("NOC Poll: Processing " . $customers->count() . " customers for OLT {$olt->name}");

                foreach ($customers as $customer) {
                    $index = $customer->onu_index;
                    $sn = $customer->onu_sn;

                    if (!$index && !$sn) {
                        continue;
                    }

                    // Match customer data from bulk SNMP results
                    $match = $gateway->matchCustomerInBulkData($bulkData, $index, $sn);

                    $dbm = $match['rx_power'];
                    $status = $match['status'];

                    // Update real index if found via SN lookup
                    if (isset($match['real_index']) && $match['real_index'] && $match['real_index'] != $index) {
                        $customer->update(['onu_index' => $match['real_index']]);
                        $this->info("Updated index for {$customer->name} to {$match['real_index']}");
                    }

                    // Update Cache
                    CustomerSignalCache::updateOrCreate(
                        ['customer_id' => $customer->id],
                        [
                            'onu_index' => $index,
                            'rx_power' => $dbm,
                            'status' => $status,
                            'last_polled_at' => now()
                        ]
                    );

                    // Log to history
                    if ($dbm !== null) {
                        $this->logHistory($customer, $dbm, $status);
                        $this->info("Result for {$customer->name}: {$dbm} dBm ({$status})");
                    } else {
                        $this->warn("No SNMP data for {$customer->name}");
                    }
                }
                
                $this->info("Successfully polled {$olt->name}");
                
            } catch (\Exception $e) {
                $this->error("Failed to poll OLT {$olt->name}: " . $e->getMessage());
                Log::error("NOC Poll Error for OLT {$olt->id}: " . $e->getMessage());
            }
        }

        $this->info('NOC Polling Completed.');
    }

    private function logHistory($customer, $dbm, $status)
    {
        $lastLog = CustomerSignalLog::where('customer_id', $customer->id)
            ->latest()
            ->first();

        // Log if:
        // 1. No history yet
        // 2. Status changed
        // 3. Signal changed significantly (0.5 dBm)
        // 4. Last log is more than 10 minutes old
        $shouldLog = !$lastLog || 
                     $lastLog->status != $status || 
                     abs($lastLog->rx_power - $dbm) >= 0.5 ||
                     $lastLog->created_at->diffInMinutes(now()) >= 10;

        if ($shouldLog) {
            CustomerSignalLog::create([
                'customer_id' => $customer->id,
                'rx_power' => $dbm,
                'status' => $status,
                'created_at' => now(),
            ]);
            $this->info("History logged for {$customer->name}");
        }
    }
}
