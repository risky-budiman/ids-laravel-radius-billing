<?php

namespace App\Services\Network;

use Exception;
use Illuminate\Support\Facades\Log;

class OltDiscoveryService
{
    protected $snmp;

    public function __construct(SnmpService $snmp)
    {
        $this->snmp = $snmp;
    }

    /**
     * Scan for unconfigured ONUs on the OLT
     */
    public function scanUnconfiguredOnus()
    {
        try {
            Log::info("Starting SNMP ONU Discovery for OLT: {$this->snmp->getHost()}");
            
            // ZTE OID for unconfigured ONUs SN: .1.3.6.1.4.1.3902.1012.3.28.1.1.5
            $results = $this->snmp->walk('.1.3.6.1.4.1.3902.1012.3.28.1.1.5');
            
            Log::debug("SNMP Walk results count: " . count($results));
            
            $onus = [];
            foreach ($results as $oid => $sn) {
                Log::debug("Found Uncfg ONU OID: {$oid}, Raw SN: " . bin2hex($sn));
                // Parse OID: .1.3.6.1.4.1.3902.1012.3.28.1.1.5.{INDEX}.{UNCONFIG_ID}
                $parts = explode('.', $oid);
                $unconfigId = array_pop($parts);
                $index = array_pop($parts);

                // Decode ZTE SNMP Index to S/S/P
                $shelf = ($index >> 24) & 0xFF;
                $slot = ($index >> 16) & 0xFF;
                $port = ($index >> 8) & 0xFF;

                $shelf = $shelf ?: 1;

                $onus[] = [
                    'sn' => $this->parseSn($sn),
                    'shelf' => $shelf,
                    'slot' => $slot,
                    'port' => $port,
                    'full_index' => "{$shelf}/{$slot}/{$port}:{$unconfigId}",
                    'oid' => $oid,
                    'type' => 'ZTE-ONU',
                ];
            }
            
            Log::info("Discovery finished. Found " . count($onus) . " unconfigured ONUs.");
            return $onus;
        } catch (Exception $e) {
            Log::error("Failed to scan unconfigured ONUs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Alias for backward compatibility on some deploys
     */
    public function scanUnconfigured()
    {
        return $this->scanUnconfiguredOnus();
    }

    /**
     * Parse Serial Number from SNMP response (ZTE format)
     */
    public function parseSn($sn)
    {
        // ZTE SNs are often returned as binary/hex
        if (strlen($sn) == 8) {
            // First 4 bytes are Vendor ID (e.g. ZTEG)
            // Last 4 bytes are Hex Serial
            $vendor = substr($sn, 0, 4);
            $hex = bin2hex(substr($sn, 4, 4));
            return $vendor . strtoupper($hex);
        }
        
        // If it's already a string or hex, try to sanitize
        return bin2hex($sn);
    }

    public function discoverPonPorts()
    {
        try {
            // ZTE Specific OID for GPON Port Description: 1.3.6.1.4.1.3902.1012.3.1.2.1.1.3
            $ifNames = $this->snmp->walk('1.3.6.1.4.1.3902.1012.3.1.2.1.1.3');
            // ZTE Specific OID for Operational Status: 1.3.6.1.4.1.3902.1012.3.1.2.1.1.7
            $ifStatus = $this->snmp->walk('1.3.6.1.4.1.3902.1012.3.1.2.1.1.7');

            if (empty($ifNames)) {
                // Fallback to standard ifNames if ZTE specific fails
                $ifNames = $this->snmp->walk('1.3.6.1.2.1.31.1.1.1.1');
                $ifStatus = $this->snmp->walk('1.3.6.1.2.1.2.2.1.8');
            }

            $ports = [];
            Log::debug("SNMP Discovery: Found " . count($ifNames) . " port names");

            foreach ($ifNames as $oid => $desc) {
                // ZTE Index is usually encoded in the OID tail
                // Example: ...1.1.3.268435457 (Rack 1, Shelf 1, Slot 1, Port 1)
                if (preg_match('/gpon-olt_(\d+)\/(\d+)\/(\d+)/i', $desc, $matches)) {
                    $shelf = $matches[1];
                    $slot = $matches[2];
                    $portNum = $matches[3];
                    
                    // Extract index from the end of the OID
                    $parts = explode('.', $oid);
                    $index = end($parts);
                    
                    // Find status for this specific index
                    $statusValue = 2; // Default inactive
                    foreach ($ifStatus as $sOid => $sVal) {
                        if (str_ends_with($sOid, ".{$index}")) {
                            $statusValue = $sVal;
                            break;
                        }
                    }

                    $ports[] = [
                        'shelf' => $shelf,
                        'slot' => $slot,
                        'port' => $portNum,
                        'status' => ($statusValue == 1) ? 'active' : 'inactive',
                        'description' => "GPON Port {$shelf}/{$slot}/{$portNum} ({$desc})"
                    ];
                }
            }
            
            return $ports;
        } catch (Exception $e) {
            Log::error("Failed to discover PON ports via SNMP: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all registered ONUs on a specific Shelf/Slot/Port via SNMP
     */
    public function getOnusOnPort($shelf, $slot, $port)
    {
        try {
            // OID for ONU SN: .1.3.6.1.4.1.3902.1012.3.28.1.1.5
            // ZTE Index format: .shelf.slot.port.onu_id
            $snOidRoot = "1.3.6.1.4.1.3902.1012.3.28.1.1.5";
            $allSns = $this->snmp->walk($snOidRoot);
            Log::debug("SNMP allSns found: " . count($allSns));
            
            // OID for Signal: .1.3.6.1.4.1.3902.1012.3.50.12.1.1.10
            $signalOidRoot = "1.3.6.1.4.1.3902.1012.3.50.12.1.1.10";
            $allSignals = [];
            try {
                $allSignals = $this->snmp->walk($signalOidRoot);
                Log::debug("SNMP allSignals found: " . count($allSignals));
            } catch (\Exception $e) {}

            $onus = [];
            $searchPrefix = ".{$shelf}.{$slot}.{$port}.";
            
            foreach ($allSns as $oid => $sn) {
                if (str_contains($oid, $searchPrefix)) {
                    $parts = explode('.', $oid);
                    $onuId = end($parts);
                    
                    // Find signal matching this OID's index
                    $signalValue = 0;
                    foreach ($allSignals as $sOid => $val) {
                        if (str_ends_with($sOid, $searchPrefix . $onuId)) {
                            $signalValue = $val;
                            break;
                        }
                    }

                    // Convert ZTE signal (0.1 dBm or 0.01 dBm)
                    $actualSignal = $signalValue > 30000 ? ($signalValue - 65536) * 0.1 : $signalValue * 0.1;
                    if ($actualSignal == 0) $actualSignal = "N/A";

                    $onus[] = [
                        'index' => "{$shelf}.{$slot}.{$port}.{$onuId}",
                        'onu_id' => $onuId,
                        'sn' => $this->parseSn($sn),
                        'signal' => $actualSignal,
                        'status' => 'online'
                    ];
                }
            }
            
            return $onus;
        } catch (\Exception $e) {
            Log::error("Failed to get ONUs via SNMP: " . $e->getMessage());
            return [];
        }
    }
}
