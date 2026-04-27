<?php

namespace App\Services\Network;

use Exception;
use Illuminate\Support\Facades\Log;

class OltDiscoveryService
{
    protected $snmp;
    
    // OIDs for ZTE ZXA10 (Standard GPON)
    // Note: OIDs can vary based on firmware. These are common for C300/C320.
    const OID_UNCONFIGURED_ONU_SN = '1.3.6.1.4.1.3902.1012.3.13.1.1.5'; // zxAnGponOnuUncfgSn
    const OID_UNCONFIGURED_ONU_TYPE = '1.3.6.1.4.1.3902.1012.3.13.1.1.2'; // zxAnGponOnuUncfgType
    
    public function __construct(SnmpService $snmp)
    {
        $this->snmp = $snmp;
    }

    /**
     * Scan for unconfigured ONUs on the OLT.
     */
    public function scanUnconfigured()
    {
        try {
            // Walk the Unconfigured ONU Serial Number table
            $rawSn = $this->snmp->walk(self::OID_UNCONFIGURED_ONU_SN);
            $rawTypes = $this->snmp->walk(self::OID_UNCONFIGURED_ONU_TYPE);
            
            $onus = [];
            
            foreach ($rawSn as $oid => $sn) {
                // Suffix format is usually .Shelf.Slot.Port.OnuIndex
                $parts = explode('.', $oid);
                $index = array_slice($parts, -4);
                
                $key = implode('.', $index);
                
                // Convert Binary SN to Hex/String if needed
                // ZTE often returns SN as binary/hex
                $formattedSn = $this->formatSerialNumber($sn);
                
                $onus[] = [
                    'sn' => $formattedSn,
                    'shelf' => $index[0] ?? 0,
                    'slot' => $index[1] ?? 0,
                    'port' => $index[2] ?? 0,
                    'raw_index' => $index[3] ?? 0,
                    'type' => $rawTypes[$oid] ?? 'Unknown',
                    'full_index' => $key
                ];
            }
            
            return $onus;
        } catch (Exception $e) {
            Log::error("OLT Discovery Scan Failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format ZTE Binary Serial Number to readable string (e.g. ZTEGC000...)
     */
    protected function formatSerialNumber($sn)
    {
        if (is_string($sn) && strlen($sn) == 8) {
            // ZTE Serial Number is usually 8 bytes
            // First 4 bytes are Vendor ID (e.g. ZTEG)
            // Last 4 bytes are Hex Serial
            $vendor = substr($sn, 0, 4);
            $hex = bin2hex(substr($sn, 4, 4));
            return $vendor . strtoupper($hex);
        }
        
        // If it's already a string or hex, try to sanitize
        return bin2hex($sn);
    }
}
