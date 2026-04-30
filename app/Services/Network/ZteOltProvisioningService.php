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

            // 1.5 Get Descriptions (Smart OLT Strategy: FreeDSx SNMP + Telnet V2)
            $descriptions = [];
            
            if ($this->olt->snmp_community) {
                try {
                    $snmpService = new \App\Services\Network\SnmpService(
                        $this->olt->ip_address, 
                        $this->olt->snmp_community, 
                        $this->olt->snmp_port ?? 161
                    );
                    
                    $portIdx = $this->calculateSnmpPortIndex($interface);
                    $oid = ".1.3.6.1.4.1.3902.1012.3.28.1.1.3.{$portIdx}";
                    
                    $snmpData = $snmpService->walk($oid);
                    
                    if (!empty($snmpData)) {
                        Log::debug("SnmpService found data for port {$interface}");
                        foreach ($snmpData as $key => $value) {
                            // Key looks like: .1.3.6.1.4.1.3902.1012.3.28.1.1.3.PORTIDX.ONUID
                            if (preg_match('/\.(\d+)$/', $key, $m)) {
                                $onuId = $m[1];
                                if ($value && $value !== 'N/A') {
                                    $descriptions[$onuId] = trim($value);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("SnmpService fetch failed: " . $e->getMessage());
                }
            }

            // OPTION 2: Try Telnet V2 Variations (Fallback)
            if (empty($descriptions)) {
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
}
