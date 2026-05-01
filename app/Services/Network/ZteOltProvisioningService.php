<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

class ZteOltProvisioningService
{
    protected $olt;
    protected $telnet;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->telnet = new CustomTelnetClient($olt->ip_address, $olt->telnet_port ?? 23);
    }

    protected function connect()
    {
        try {
            if (!$this->telnet->connect()) {
                return false;
            }

            // Give OLT a moment to send banner/username prompt
            usleep(200000); // 0.2 seconds

            // Standard Login
            $this->telnet->read('/Username:/i');
            $this->telnet->write($this->olt->username . "\r\n");
            $this->telnet->read('/Password:/i');
            $this->telnet->write($this->olt->password . "\r\n");
            
            $prompt = $this->telnet->read('/ZXAN[>#]/i');
            
            // If we are at ">", we need to send "enable"
            if (strpos($prompt, '>') !== false) {
                $this->telnet->write("enable\r\n");
                $res = $this->telnet->read(['/Password:/i', '/ZXAN#/i']);
                
                if (stripos($res, 'Password:') !== false) {
                    // Give OLT a moment to breathe before sending password
                    usleep(500000); // 0.5 seconds
                    $this->telnet->write(($this->olt->enable_password ?: $this->olt->password) . "\r\n");
                    $this->telnet->read('/ZXAN#/i');
                }
            }
            
            // Turn off pagination
            $this->telnet->write("terminal length 0\r\n");
            $this->telnet->read('/ZXAN#/i');
            
            return true;
        } catch (\Exception $e) {
            Log::error("OLT Telnet Connection Error: " . $e->getMessage());
            return false;
        }
    }

    public function discoverPortsViaCli()
    {
        try {
            if (!$this->connect()) return [];

            $this->telnet->write("show card\r\n");
            $output = $this->telnet->read('/[>#]$/');
            Log::debug("OLT Card Output: " . $output);

            $ports = [];
            $lines = explode("\n", $output);
            
            foreach ($lines as $line) {
                // Match GPON cards like GTGH, GTGO, etc.
                if (preg_match('/(\d+)\s+(\d+)\s+(\d+)\s+(GTG[HO]|GPON|PUMA)\S*\s+\S*\s+(\d+)/i', $line, $matches)) {
                    $shelf = $matches[2];
                    $slot = $matches[3];
                    $maxPorts = $matches[5];

                    for ($p = 1; $p <= $maxPorts; $p++) {
                        $ports[] = [
                            'shelf' => $shelf,
                            'slot' => $slot,
                            'port' => $p,
                            'name' => "GPON {$shelf}/{$slot}/{$p}",
                            'type' => 'GPON'
                        ];
                    }
                }
            }

            $this->telnet->disconnect();
            return $ports;
        } catch (\Exception $e) {
            Log::error("OLT Port Discovery Error: " . $e->getMessage());
            return [];
        }
    }

    public function getOnusOnPortViaCli($shelf, $slot, $port)
    {
        try {
            if (!$this->connect()) return [];
            
            // Default shelf to 1 if empty
            $shelf = $shelf ?: 1;
            $interface = "gpon-olt_{$shelf}/{$slot}/{$port}";

            // 1. Get Running Config (Contains SN and Type)
            $this->telnet->write("show running-config interface {$interface}\r\n");
            $configOutput = $this->telnet->read('/ZXAN#/i');
            Log::debug("OLT Raw Config Output for {$interface}: " . $configOutput);

            // 1.5 Get Descriptions (SNMP FIRST PRIORITY)
            $descriptions = [];
            
            if ($this->olt->snmp_read_community) {
                // METHOD A: Use Internal SnmpService (FreeDSx Library)
                try {
                    Log::debug("Attempting SNMP Fetch (Method A: Library) for port {$interface}");
                    $snmpService = new \App\Services\Network\SnmpService(
                        $this->olt->ip_address, 
                        $this->olt->snmp_read_community, 
                        $this->olt->snmp_port ?? 161
                    );
                    
                    $portIdx = $this->calculateSnmpPortIndex($interface);
                    
                    // 1. Try standard C300 series OID (Port Specific)
                    $oidC300 = ".1.3.6.1.4.1.3902.1012.3.28.1.1.3.{$portIdx}";
                    $snmpData = $snmpService->walk($oidC300);
                    if (!empty($snmpData)) {
                        foreach ($snmpData as $key => $value) {
                            if (preg_match('/\.(\d+)$/', $key, $m)) {
                                $onuId = $m[1];
                                if ($value && $value !== 'N/A') $descriptions[$onuId] = trim($value);
                            }
                        }
                    }

                    // 2. Try Titan C600 series OID (Base Walk + Filter)
                    if (empty($descriptions)) {
                        $oidTitan = ".1.3.6.1.4.1.3902.1082.10.1.2.4.1.4";
                        $titanData = $snmpService->walk($oidTitan);
                        if (!empty($titanData)) {
                            foreach ($titanData as $key => $value) {
                                // Titan OID format usually: ...4.1.4.{PORT_IDX}.{ONU_IDX}
                                if (str_contains($key, ".{$portIdx}.")) {
                                    $parts = explode('.', $key);
                                    $onuId = end($parts);
                                    if ($value && $value !== 'N/A') $descriptions[$onuId] = trim($value);
                                }
                            }
                        }
                    }

                    if (!empty($descriptions)) {
                        Log::info("Successfully fetched " . count($descriptions) . " names via SNMP.");
                    }
                } catch (\Exception $e) {
                    Log::warning("SNMP Library Fetch failed: " . $e->getMessage());
                }

                // METHOD B: Use System snmpwalk (Especially good for Ubuntu)
                if (empty($descriptions)) {
                    try {
                        Log::debug("Attempting SNMP Fetch (Method B: snmpwalk) for port {$interface}");
                        $ip = $this->olt->ip_address;
                        $community = $this->olt->snmp_read_community;
                        $portIdx = $this->calculateSnmpPortIndex($interface);
                        
                        // 1. Try C300 OID
                        $cmdC300 = "snmpwalk -v2c -c {$community} {$ip} .1.3.6.1.4.1.3902.1012.3.28.1.1.3.{$portIdx} 2>&1";
                        $outC300 = shell_exec($cmdC300);
                        if ($outC300 && preg_match_all('/\.(\d+)\s+=\s+STRING:\s+"([^"]+)"/i', $outC300, $mC300, PREG_SET_ORDER)) {
                            foreach ($mC300 as $m) {
                                if ($m[2] !== 'N/A') $descriptions[$m[1]] = trim($m[2]);
                            }
                        }

                        // 2. Try Titan C600 OID (Walk Base + Filter)
                        if (empty($descriptions)) {
                            $cmdTitan = "snmpwalk -v2c -c {$community} {$ip} .1.3.6.1.4.1.3902.1082.10.1.2.4.1.4 2>&1";
                            $outTitan = shell_exec($cmdTitan);
                            if ($outTitan && preg_match_all('/\.(\d+)\.(\d+)\s+=\s+STRING:\s+"([^"]+)"/i', $outTitan, $mTitan, PREG_SET_ORDER)) {
                                foreach ($mTitan as $m) {
                                    if ($m[1] == $portIdx && $m[3] !== 'N/A') {
                                        $descriptions[$m[2]] = trim($m[3]);
                                    }
                                }
                            }
                        }
                        
                        if (!empty($descriptions)) Log::info("Successfully fetched " . count($descriptions) . " names via System snmpwalk.");
                    } catch (\Exception $e) {
                        Log::warning("System snmpwalk failed: " . $e->getMessage());
                    }
                }
            }

            // OPTION 2: Try Telnet V2 Variations (Fallback Only)
            if (empty($descriptions)) {
                Log::debug("SNMP failed, falling back to Telnet for descriptions on port {$interface}");
                // Variation 1: show onu description (Common in V2)
                $this->telnet->write("show onu description {$interface}\r\n");
                $v2Output = $this->telnet->read('/ZXAN#/i');
                
                if (preg_match_all('/' . preg_quote($interface, '/') . ':(\d+)\s+(.*)/i', $v2Output, $v2Matches, PREG_SET_ORDER)) {
                    foreach ($v2Matches as $vm) {
                        $descriptions[$vm[1]] = trim($vm[2]);
                    }
                }
            }
            
            // 2. Get Power (Attenuation)
            $this->telnet->write("show pon power onu-rx {$interface}\r\n");
            $powerOutput = $this->telnet->read('/ZXAN#/i');
            
            // 3. Get State
            $this->telnet->write("show gpon onu state {$interface}\r\n");
            $stateOutput = $this->telnet->read('/ZXAN#/i');

            $onus = [];
            
            // Step 1: Parse SNs and Types from config
            preg_match_all('/onu (\d+) type (\S+) sn (\S+)/i', $configOutput, $snMatches, PREG_SET_ORDER);
            
            foreach ($snMatches as $match) {
                $onuId = $match[1];
                $type = $match[2];
                $sn = str_ireplace('SN:', '', $match[3]);
                
                $description = $descriptions[$onuId] ?? 'ONU ' . $onuId;

                // Extract phase state/reason from state output
                $reason = 'Unknown';
                if (preg_match("/:{$onuId}\s+\S+\s+\S+\s+(\S+)/i", $stateOutput, $sMatches)) {
                    $reason = $sMatches[1];
                }
                
                // Signal from power output
                $signal = 'N/A';
                if (preg_match("/:{$onuId}\s+([-+]?\d+\.?\d*)/i", $powerOutput, $pMatches)) {
                    $signal = $pMatches[1];
                }

                $finalStatus = (strtolower($reason) === 'working') ? 'online' : 'offline';

                $onus[] = [
                    'index' => "{$shelf}.{$slot}.{$port}.{$onuId}",
                    'onu_id' => $onuId,
                    'sn' => $sn,
                    'name' => $description,
                    'type' => $type,
                    'status' => $finalStatus,
                    'reason' => $reason,
                    'signal' => $finalStatus === 'online' ? $signal : 'LOST'
                ];
            }
            
            $this->telnet->disconnect();
            return $onus;
        } catch (\Exception $e) {
            Log::error("OLT ONU Discovery Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get OLT-level statistics (CPU, Uptime) via SNMP
     */
    public function getOltStats()
    {
        if (!$this->olt->snmp_read_community) return null;

        try {
            $snmpService = new \App\Services\Network\SnmpService(
                $this->olt->ip_address, 
                $this->olt->snmp_read_community, 
                $this->olt->snmp_port ?? 161
            );

            // 1. Check Uptime First (Standard Global OID - if this works, OLT is ONLINE)
            $status = 'offline';
            $uptime = 'N/A';
            try {
                $rawUptime = $snmpService->get('.1.3.6.1.2.1.1.3.0');
                if ($rawUptime) {
                    $status = 'online';
                    $uptime = $this->formatTimeticks((string)$rawUptime);
                }
            } catch (\Exception $e) {
                Log::debug("SNMP Uptime check failed for {$this->olt->ip_address}");
            }

            if ($status === 'offline') {
                return ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
            }

            // 2. Fetch Extra Stats (CPU & Temp)
            $cpu = 0;
            $temp = 0;
            
            Log::info("Starting Hardware Debug for OLT: {$this->olt->ip_address}");

            // Dynamic CPU Discovery (Try ZTE Titan, ZTE C300, then Global)
            $cpuOidTables = [
                '.1.3.6.1.4.1.3902.1082.10.1.3.1.1.3', // ZTE Titan CPU (New)
                '.1.3.6.1.4.1.3902.1012.3.1.3.1.1.3', // ZTE C300 CPU
                '.1.3.6.1.2.1.25.3.3.1.2',           // Global Host Resources CPU
            ];

            foreach ($cpuOidTables as $tableOid) {
                try {
                    $cpuTable = $snmpService->walk($tableOid);
                    Log::debug("CPU Table Walk for {$tableOid}: " . json_encode($cpuTable));
                    if (!empty($cpuTable)) {
                        foreach ($cpuTable as $val) {
                            if ((int)$val > 0 && (int)$val <= 100) {
                                $cpu = (int)$val;
                                break 2;
                            }
                        }
                    }
                } catch (\Exception $e) { }
            }

            // Dynamic Temp Discovery (ZTE Titan then ZTE C300)
            $tempOidTables = [
                '.1.3.6.1.4.1.3902.1082.10.1.3.1.1.2', // ZTE Titan Temp (New)
                '.1.3.6.1.4.1.3902.1012.3.1.2.1.1.3', // ZTE C300 Temp
            ];

            foreach ($tempOidTables as $tableOid) {
                try {
                    $tempTable = $snmpService->walk($tableOid);
                    Log::debug("Temp Table Walk for {$tableOid}: " . json_encode($tempTable));
                    if (!empty($tempTable)) {
                        foreach ($tempTable as $val) {
                            if ((int)$val > 10 && (int)$val < 100) { 
                                $temp = (int)$val;
                                break 2;
                            }
                        }
                    }
                } catch (\Exception $e) { }
            }

            Log::info("Hardware Stats Final: CPU={$cpu}%, Temp={$temp}C");

            return [
                'status' => 'online',
                'cpu' => $cpu,
                'uptime' => $uptime,
                'temp' => $temp
            ];
        } catch (\Exception $e) {
            return ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
        }
    }

    /**
     * Test the Telnet connection to the OLT
     */
    public function testConnection()
    {
        try {
            $this->connect();
            $this->telnet->disconnect();
            return true;
        } catch (\Exception $e) {
            Log::error("OLT Connection Test Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate the SNMP index for a given interface string (e.g., 1/1/13)
     * Formula for ZTE GPON: (shelf << 24) | (slot << 16) | (port << 8)
     */
    private function calculateSnmpPortIndex($interface)
    {
        // Remove 'gpon-olt_' or similar prefix
        $clean = preg_replace('/[^0-9\/]/', '', $interface);
        $parts = explode('/', $clean);
        
        if (count($parts) < 3) return 0;
        
        $shelf = (int)$parts[0];
        $slot = (int)$parts[1];
        $port = (int)$parts[2];
        
        // Typical ZTE GPON Index calculation
        return ($shelf << 24) | ($slot << 16) | ($port << 8);
    }

    /**
     * Format SNMP Timeticks to Human Readable string
     */
    private function formatTimeticks($ticks)
    {
        // If it already contains a time format like "0:02:03.45"
        if (preg_match('/(\d+):(\d+):(\d+)/', $ticks)) {
            // If it has the (ticks) prefix, just take the part after it
            if (preg_match('/\)\s*(.*)/', $ticks, $m)) {
                return $m[1];
            }
            return $ticks;
        }
        
        // Clean up: remove non-numeric
        $ticksVal = preg_replace('/[^0-9]/', '', $ticks);
        if (!$ticksVal || !is_numeric($ticksVal)) return $ticks;

        $seconds = (int)($ticksVal / 100);
        $days = floor($seconds / 86400);
        $seconds %= 86400;
        $hours = floor($seconds / 3600);
        $seconds %= 3600;
        $minutes = floor($seconds / 60);
        $seconds %= 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        $parts[] = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        
        return implode(" ", $parts);
    }
}
