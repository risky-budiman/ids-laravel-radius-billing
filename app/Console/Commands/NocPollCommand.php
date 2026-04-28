<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Olt;
use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Services\Network\SnmpService;
use App\Services\Network\OltManagerService;
use Illuminate\Support\Facades\Log;

class NocPollCommand extends Command
{
    protected $signature = 'noc:poll';
    protected $description = 'Poll OLTs for ONU status and signal power via SNMP Bulk Walk';

    public function handle()
    {
        $this->info('Starting NOC SNMP Polling...');
        
        $olts = Olt::where('is_active', true)->get();
        
        foreach ($olts as $olt) {
            $this->info("Polling OLT: {$olt->name} ({$olt->ip_address})...");
            
            try {
                $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
                
                // 1. Walk Rx Power
                $rxResults = $snmp->walk(OltManagerService::OID_ONU_RX_POWER);
                
                // 2. Walk Status (Optional but good)
                $statusResults = $snmp->walk(OltManagerService::OID_ONU_STATUS);
                
                $this->info("Found " . count($rxResults) . " signals.");

                foreach ($rxResults as $oid => $value) {
                    // Extract index from OID (the part after the base OID)
                    $index = str_replace(OltManagerService::OID_ONU_RX_POWER . '.', '', $oid);
                    
                    // Find customer by OLT ID and ONU Index
                    $customer = Customer::where('olt_id', $olt->id)
                                       ->where('onu_index', $index)
                                       ->first();
                    
                    if ($customer) {
                        $power = (float)$value;
                        if ($power == 65535000 || $power == 0) {
                            $dbm = null;
                        } else {
                            $dbm = round($power / 1000, 2);
                        }

                        // Get status from status results if available
                        $statusValue = $statusResults[OltManagerService::OID_ONU_STATUS . '.' . $index] ?? null;
                        $status = $this->parseStatus($statusValue);

                        // Detect Critical Signal for Alerting with Throttling (once every 6 hours)
                        if ($dbm !== null && $dbm < -27) {
                            $cache = CustomerSignalCache::where('customer_id', $customer->id)->first();
                            $shouldAlert = !$cache || !$cache->last_alerted_at || $cache->last_alerted_at->addHours(6)->isPast();

                            if ($shouldAlert) {
                                $alertService = new \App\Services\Network\NocAlertService();
                                $alertService->sendAlert("🔴 *SIGNAL CRITICAL*\nPelanggan: {$customer->name}\nUsername: {$customer->username}\nRedaman: {$dbm} dBm\nOLT: {$olt->name}");
                                
                                // We will update last_alerted_at in the updateOrCreate below
                                $lastAlertedAt = now();
                            } else {
                                $lastAlertedAt = $cache->last_alerted_at;
                            }
                        } else {
                            $lastAlertedAt = null; // Reset alert if signal is good
                        }

                        CustomerSignalCache::updateOrCreate(
                            ['customer_id' => $customer->id],
                            [
                                'onu_index' => $index,
                                'rx_power' => $dbm,
                                'status' => $status,
                                'last_polled_at' => now(),
                                'last_alerted_at' => $lastAlertedAt
                            ]
                        );

                        // Save to History Logs (Only once per hour to save space)
                        $lastLog = \App\Models\CustomerSignalLog::where('customer_id', $customer->id)
                                    ->orderBy('created_at', 'desc')
                                    ->first();
                        
                        if (!$lastLog || $lastLog->created_at->addHour()->isPast() || $lastLog->status != $status) {
                            \App\Models\CustomerSignalLog::create([
                                'customer_id' => $customer->id,
                                'rx_power' => $dbm,
                                'status' => $status,
                                'created_at' => now()
                            ]);
                        }
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

    private function parseStatus($val)
    {
        // Based on ZTE MIB: 1:logging, 2:los, 3:syncloss, 4:online, 5:dyinggasp, 6:authFailed, 7:offline
        switch ($val) {
            case 4: return 'online';
            case 2: return 'los';
            case 3: return 'los';
            case 5: return 'dying-gasp';
            case 7: return 'offline';
            default: return 'unknown';
        }
    }
}
