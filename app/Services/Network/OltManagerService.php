<?php

namespace App\Services\Network;

use App\Models\Olt;
use Exception;
use Illuminate\Support\Facades\Log;

class OltManagerService
{
    protected $olt;
    protected $snmp;

    // OIDs for ZTE Optical Monitoring
    const OID_ONU_RX_POWER = '1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.2'; // OLT side Rx (from ONU)
    const OID_ONU_STATUS = '1.3.6.1.4.1.3902.1012.3.28.1.1.3'; // zxAnOnuStatus
    
    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
    }

    /**
     * Get Optical Power for a specific ONU.
     * Index format: .shelf.slot.port.onu_id
     */
    public function getOnuOpticalPower(string $index)
    {
        try {
            $raw = $this->snmp->get(self::OID_ONU_RX_POWER . '.' . $index);
            
            // Value is usually in 0.002 dBm or 0.001 dBm units
            // ZTE C320 often uses 0.001 dBm
            $power = (float)$raw;
            
            if ($power == 65535000 || $power == 0) return 'N/A';
            
            // Formula for ZTE: (value - 100000) / 1000 if using that specific MIB
            // Or just value / 1000. 
            // Most common: value / 1000
            $dbm = $power / 1000;
            
            return round($dbm, 2) . ' dBm';
        } catch (Exception $e) {
            return 'Error';
        }
    }

    /**
     * Reboot an ONU via SNMP.
     * This is highly OLT/Firmware dependent. 
     * OID: zxAnOnuAdminOperation (1.3.6.1.4.1.3902.1012.3.28.1.1.1)
     * Value 1 = reboot (example)
     */
    public function rebootOnu(string $index)
    {
        try {
            $writeSnmp = new SnmpService($this->olt->ip_address, $this->olt->snmp_write_community, $this->olt->snmp_port);
            // Example OID for operation. Values: 1: reboot, 2: reset-factory
            $oid = '1.3.6.1.4.1.3902.1012.3.28.1.1.1.' . $index;
            $writeSnmp->set($oid, 1); 
            return true;
        } catch (Exception $e) {
            Log::error("Failed to reboot ONU via SNMP: " . $e->getMessage());
            return false;
        }
    }
}
