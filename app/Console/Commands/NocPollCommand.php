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
    protected $signature = 'noc:monitor';
    protected $description = 'Poll OLTs for ONU status and signal power via SNMP Bulk Walk';

    public function handle()
    {
        $this->info('Starting NOC SNMP Polling...');
        \Illuminate\Support\Facades\Log::info("NOC Poll: Starting execution...");
        
        $olts = Olt::where('is_active', true)->get();
        \Illuminate\Support\Facades\Log::info("NOC Poll: Found " . $olts->count() . " active OLTs.");

        if ($olts->isEmpty()) {
            \Illuminate\Support\Facades\Log::warning("NOC Poll: No active OLTs found to poll.");
            return;
        }
        $rxOids = [
            '1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.2', // Titan C600 (OLT side Rx)
            '1.3.6.1.4.1.3902.1012.3.50.12.1.1.10',  // C300/C320 (ONU side Rx)
            '1.3.6.1.4.1.3902.1012.3.28.1.1.3',      // Status OID (Fallback for discovery)
        ];

        foreach ($olts as $olt) {
            $this->info("Polling OLT: {$olt->name} ({$olt->ip_address})...");
            
            try {
                $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
                
                $rxResults = [];
                $statusResults = [];
                
                // Try walking multiple OIDs to find which one works for this OLT
                foreach ($rxOids as $oid) {
                    $results = $snmp->walk($oid);
                    if (count($results) > 0) {
                        if ($oid === '1.3.6.1.4.1.3902.1012.3.28.1.1.3') {
                            $statusResults = $results;
                        } else {
                            $rxResults = $results;
                            // If we found RX Power, we also need status
                            $statusResults = $snmp->walk('1.3.6.1.4.1.3902.1012.3.28.1.1.3');
                        }
                        $this->info("Using OID: {$oid} (Found " . count($results) . " items)");
                        break; 
                }

                $customers = Customer::where('olt_id', $olt->id)->get();
                \Illuminate\Support\Facades\Log::info("NOC Poll: Processing " . $customers->count() . " customers for OLT {$olt->name}");

                foreach ($customers as $customer) {
                    $this->info("Polling customer: {$customer->name}");
                    $index = $customer->onu_index;
                    $sn = $customer->onu_sn;

                    if (!$index && !$sn) {
                        $this->info("Skipping {$customer->name}: No index or SN.");
                        continue;
                    }

                    $dbm = null;
                    $status = 'unknown';

                    // 1. Try to find in SNMP maps if we have an index
                    if ($index) {
                        $dotIndex = str_replace(['/', ':'], '.', ltrim($index, '.'));
                        $integerIndex = null;
                        $parts = explode('.', $dotIndex);
                        if (count($parts) >= 3) {
                            $integerIndex = (string)(((int)$parts[0] << 24) | ((int)$parts[1] << 16) | ((int)$parts[2] << 8) | (isset($parts[3]) ? (int)$parts[3] : 0));
                        }

                        foreach ([$dotIndex, $integerIndex] as $search) {
                            if (!$search) continue;
                            foreach ($rxResults as $tail => $val) {
                                if (str_ends_with($tail, $search)) {
                                    $power = (float)$val;
                                    if ($power != 65535000 && $power != 0) {
                                        $dbm = ($power > 1000 || $power < -1000) ? round($power / 1000, 2) : round($power * 0.1, 2);
                                    }
                                    break;
                                }
                            }
                            foreach ($statusResults as $tail => $val) {
                                if (str_ends_with($tail, $search)) {
                                    $status = $this->parseStatus($val);
                                    break;
                                }
                            }
                            if ($dbm !== null) break;
                        }
                    }

                    // 2. FORCED FALLBACK: If SNMP gave nothing, use Telnet!
                    if ($dbm === null || $status === 'unknown') {
                        $this->info("SNMP no data for {$customer->name}, using Telnet...");
                        $telnetResult = null;
                        
                        if ($index) {
                            $telnetResult = $this->fetchViaTelnet($olt, $index);
                        }
                        
                        if ((!$telnetResult || $telnetResult['rx_power'] === null) && $sn) {
                            $this->info("Trying SN lookup for {$customer->name} ({$sn})...");
                            $telnetResult = $this->fetchBySn($olt, $sn);
                        }

                        if ($telnetResult) {
                            $dbm = $telnetResult['rx_power'] ?? $dbm;
                            $status = $telnetResult['status'] ?? $status;
                            
                            if (isset($telnetResult['real_index']) && $telnetResult['real_index'] != $index) {
                                $customer->update(['onu_index' => $telnetResult['real_index']]);
                                $this->info("Updated index for {$customer->name} to {$telnetResult['real_index']}");
                            }
                        }
                    }

                    if ($dbm !== null) {
                        $this->logHistory($customer, $dbm, $status);
                        $this->info("Result for {$customer->name}: {$dbm} dBm ({$status})");
                    } else {
                        $this->warn("Failed to get data for {$customer->name}");
                    }
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
                    
                    // Log to history if status changed or 1 hour passed
                    $this->logHistory($customer, $dbm, $status);
                }
                
                $this->info("Successfully polled {$olt->name}");
                
            } catch (\Exception $e) {
                $this->error("Failed to poll OLT {$olt->name}: " . $e->getMessage());
                Log::error("NOC Poll Error for OLT {$olt->id}: " . $e->getMessage());
            }
        }

        $this->info('NOC Polling Completed.');
    }

    private function fetchViaTelnet($olt, $index)
    {
        try {
            // Convert index to CLI format if needed (e.g. 1/1/1:1)
            $cliIndex = str_replace(['.', '/'], '/', ltrim($index, '.'));
            $parts = explode('/', $cliIndex);
            if (count($parts) == 4) {
                $cliIndex = "{$parts[0]}/{$parts[1]}/{$parts[2]}:{$parts[3]}";
            }

            // Use a longer timeout for busy OLTs
            $client = new \App\Services\Network\CustomTelnetClient($olt->ip_address, $olt->telnet_port ?? 23, 20); // 20s timeout
            if (!$client->connect()) return null;

            // Simple login - support both > and # prompts
            $client->read('/(?:Username:|login:)/i');
            $client->write($olt->username . "\r\n");
            $client->read('/Password:/i');
            $client->write($olt->password . "\r\n");
            $output = $client->read('/ZXAN[>#]/i'); 
            
            // If we are in user mode (>), try to 'enable'
            if (str_contains($output, '>')) {
                $client->write("enable\r\n");
                $resp = $client->read('/(?:Password:|ZXAN#)/i');
                if (str_contains($resp, 'Password')) {
                    // Force clear buffer before password
                    $client->read(null); 
                    usleep(1000000); 
                    
                    $enPass = $olt->enable_password ?: "zxr10"; // Try database or default
                    $client->write($enPass . "\r\n");
                    
                    $after = $client->read('/ZXAN#/i');
                    if (str_contains($after, 'Bad password')) {
                        // One last try with login password if different
                        if ($olt->password != $enPass) {
                            $client->write("enable\r\n");
                            $client->read('/Password:/i');
                            $client->write($olt->password . "\r\n");
                            $client->read('/ZXAN#/i');
                        }
                    }
                }
            }

            $client->write("terminal length 0\r\n");
            $client->read('/ZXAN[>#]/i');

            $res = ['rx_power' => null, 'status' => 'unknown'];

            // Try multiple commands for power in order of reliability
            $commands = [
                "show gpon onu remote-info optical-info gpon-onu_{$cliIndex}",
                "show pon onu rx-power gpon-onu_{$cliIndex}",
                "show onu rx-power gpon-onu_{$cliIndex}",
                "show onu optical-info gpon-onu_{$cliIndex}",
                "show pon onu optical-info gpon-onu_{$cliIndex}",
                "show pon onu information gpon-onu_{$cliIndex}",
                "show gpon onu detail-info gpon-onu_{$cliIndex}"
            ];

            foreach ($commands as $cmd) {
                $this->info("Trying command: {$cmd}");
                $client->write($cmd . "\r\n");
                $output = $client->read('/ZXAN[>#]/i');
                
                \Illuminate\Support\Facades\Log::debug("Command Output for [{$cmd}]:\n" . $output);
                
                $this->parseTelnetOutput($output, $res);
                if ($res['rx_power'] !== null) {
                    $this->info("Got RX Power: {$res['rx_power']} dBm using {$cmd}");
                    break;
                }
            }

            $client->disconnect();
            return $res;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Telnet Poll Error: " . $e->getMessage());
            return null;
        }
    }

    private function fetchBySn($olt, $sn)
    {
        try {
            $client = new \App\Services\Network\CustomTelnetClient($olt->ip_address, $olt->telnet_port ?? 23, 20);
            if (!$client->connect()) return null;

            // Simple login - support both > and # prompts
            $client->read('/(?:Username:|login:)/i');
            $client->write($olt->username . "\r\n");
            $client->read('/Password:/i');
            $client->write($olt->password . "\r\n");
            $output = $client->read('/ZXAN[>#]/i'); 
            
            // If we are in user mode (>), try to 'enable'
            if (str_contains($output, '>')) {
                $client->write("enable\r\n");
                $resp = $client->read('/(?:Password:|ZXAN#)/i');
                if (str_contains($resp, 'Password')) {
                    $client->read(null); // Clear
                    usleep(1000000); 
                    
                    $enPass = $olt->enable_password ?: "zxr10";
                    $client->write($enPass . "\r\n");
                    
                    $after = $client->read('/ZXAN#/i');
                    if (str_contains($after, 'Bad password')) {
                        if ($olt->password != $enPass) {
                            $client->write("enable\r\n");
                            $client->read('/Password:/i');
                            $client->write($olt->password . "\r\n");
                            $client->read('/ZXAN#/i');
                        }
                    }
                }
            }

            $client->write("terminal length 0\r\n");
            $client->read('/ZXAN[>#]/i');

            // Find by SN
            $client->write("show gpon onu by-sn {$sn}\r\n");
            $output = $client->read('/ZXAN[>#]/i');
            
            // Extract port and ONU ID
            // Priority 1: Find port (1/1/1) and ONU ID (105) separately
            $port = null;
            $onuId = null;
            if (preg_match('/ONU interface[:\s]+(?:gpon-onu_)?(\d+\/\d+\/\d+)/i', $output, $m)) {
                $port = $m[1];
            }
            if (preg_match('/ONU ID[:\s]+(\d+)/i', $output, $m)) {
                $onuId = $m[1];
            }

            $realIndex = null;
            if ($port && $onuId) {
                $realIndex = "{$port}:{$onuId}";
            } elseif (preg_match('/(?:ONU interface|gpon-onu_)[:\s]+(\d+\/\d+\/\d+):(\d+)/i', $output, $m)) {
                $realIndex = "{$m[1]}:{$m[2]}";
            }

            if ($realIndex) {
                $this->info("Found real index: {$realIndex} for SN {$sn}");
                $res = ['rx_power' => null, 'status' => 'unknown', 'real_index' => $realIndex];
                
                // Try multiple commands for power in order of reliability
                $commands = [
                    "show gpon onu remote-info optical-info gpon-onu_{$realIndex}",
                    "show pon onu rx-power gpon-onu_{$realIndex}",
                    "show onu rx-power gpon-onu_{$realIndex}",
                    "show onu optical-info gpon-onu_{$realIndex}",
                    "show pon onu optical-info gpon-onu_{$realIndex}",
                    "show pon onu information gpon-onu_{$realIndex}",
                    "show gpon onu detail-info gpon-onu_{$realIndex}"
                ];

                foreach ($commands as $cmd) {
                    $this->info("Trying command: {$cmd}");
                    $client->write($cmd . "\r\n");
                    $pOutput = $client->read('/ZXAN[>#]/i');
                    
                    // Log EVERY output for debugging
                    \Illuminate\Support\Facades\Log::debug("Command Output for [{$cmd}]:\n" . $pOutput);
                    
                    $this->parseTelnetOutput($pOutput, $res);
                    if ($res['rx_power'] !== null) {
                        $this->info("Got RX Power: {$res['rx_power']} dBm using {$cmd}");
                        break;
                    }
                }

                $client->disconnect();
                return $res;
            }

            $client->disconnect();
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTelnetOutput($output, &$res)
    {
        // Debug: Log raw output to help adjust regex
        if (strlen(trim($output)) > 0) {
            \Illuminate\Support\Facades\Log::debug("PARSING OLT OUTPUT:\n" . $output);
        }

        // Super Aggressive Regex for RX Power
        // Matches anything like: Rx: -20.5, Rx Power: -20.5, Rx optical power: -20.5, etc.
        if (preg_match('/(?:Rx|Optical|ONT|OLT).*?power.*?[?:\s]+(-?\d+\.?\d*)/i', $output, $m)) {
            $res['rx_power'] = (float)$m[1];
        } elseif (preg_match('/Rx.*?(-?\d+\.?\d*)\s*(?:dBm|db)/i', $output, $m)) {
            $res['rx_power'] = (float)$m[1];
        }
        
        // Aggressive Status Check
        if (preg_match('/(?:Phase|Admin|Operation|State)\s+(?:state|status)?[:\s]+(\w+)/i', $output, $m)) {
            $state = strtolower($m[1]);
            if (str_contains($state, 'work') || str_contains($state, 'up') || str_contains($state, 'on') || str_contains($state, 'ready')) {
                $res['status'] = 'online';
            } elseif (str_contains($state, 'los') || str_contains($state, 'off') || str_contains($state, 'down')) {
                $res['status'] = 'los';
            } else {
                $res['status'] = $state;
            }
        }
    }

    private function logHistory($customer, $dbm, $status)
    {
        $lastLog = \App\Models\CustomerSignalLog::where('customer_id', $customer->id)
            ->latest()
            ->first();

        // Log if:
        // 1. No history yet
        // 2. Status changed
        // 3. Signal changed significantly (0.5 dBm)
        // 4. Last log is more than 10 minutes old (more frequent for now)
        $shouldLog = !$lastLog || 
                     $lastLog->status != $status || 
                     abs($lastLog->rx_power - $dbm) >= 0.5 ||
                     $lastLog->created_at->diffInMinutes(now()) >= 10;

        if ($shouldLog) {
            \App\Models\CustomerSignalLog::create([
                'customer_id' => $customer->id,
                'rx_power' => $dbm,
                'status' => $status,
                'created_at' => now(),
            ]);
            $this->info("History logged for {$customer->name}");
        }
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
