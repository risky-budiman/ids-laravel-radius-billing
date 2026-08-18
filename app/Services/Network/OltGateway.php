<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

/**
 * OLT Gateway — Pure SNMP Communication Layer.
 * 
 * Single entry point for all OLT operations.
 * All monitoring, discovery, port scanning, and signal checks use SNMP exclusively.
 * Multi-MIB fallback supports ZTE C300 V1, C320 V2, and Titan C600.
 */
class OltGateway
{
    protected Olt $olt;
    protected ?SnmpService $snmp = null;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
    }

    /**
     * Lazy-load and reuse SNMP connection.
     */
    public function snmp(): SnmpService
    {
        if (!$this->snmp) {
            $this->snmp = SnmpService::fromOlt($this->olt);
        }
        return $this->snmp;
    }

    // ═════════════════════════════════════════════════════
    // CONNECTION TESTING (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Test SNMP connectivity to the OLT.
     */
    public function testSnmpConnection(): array
    {
        return $this->snmp()->testConnection();
    }

    // ═════════════════════════════════════════════════════
    // OLT SYSTEM STATUS (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Get OLT hardware status: uptime, CPU, temperature, online status.
     */
    public function getOltStatus(): array
    {
        $rawUptime = $this->snmp()->getSafe(ZteOids::SYS_UPTIME);

        if ($rawUptime === null) {
            // Try fallback: sysName or sysDescr
            $sysName = $this->snmp()->getSafe(ZteOids::SYS_NAME);
            if ($sysName === null) {
                return ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
            }
        }

        $uptime = $rawUptime ? ZteOids::formatUptime((string) $rawUptime) : 'Connected';

        // CPU (try multiple tables)
        $cpu = 0;
        foreach (ZteOids::CPU_OIDS as $oid) {
            $cpuTable = $this->snmp()->walkSafe($oid);
            if (!empty($cpuTable)) {
                foreach ($cpuTable as $val) {
                    if ((int)$val > 0 && (int)$val <= 100) {
                        $cpu = (int)$val;
                        break 2;
                    }
                }
            }
        }

        // Temperature (try multiple tables)
        $temp = 0;
        foreach (ZteOids::TEMP_OIDS as $oid) {
            $tempTable = $this->snmp()->walkSafe($oid);
            if (!empty($tempTable)) {
                foreach ($tempTable as $val) {
                    if ((int)$val > 10 && (int)$val < 100) {
                        $temp = (int)$val;
                        break 2;
                    }
                }
            }
        }

        return [
            'status' => 'online',
            'cpu'    => $cpu,
            'uptime' => $uptime,
            'temp'   => $temp,
        ];
    }

    // ═════════════════════════════════════════════════════
    // PON PORT DISCOVERY (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Discover all PON ports on the OLT via SNMP.
     * Uses interface tables + ONU registration tables to guarantee 100% port discovery.
     */
    public function discoverPonPorts(): array
    {
        $portsMap = [];
        $activePortKeys = [];

        // 1. Scan registered ONUs to identify active slots and ports
        foreach (ZteOids::ONU_SN_TABLES as $tableOid) {
            $sns = $this->snmp()->walkSafe($tableOid);
            if (!empty($sns)) {
                foreach ($sns as $oid => $rawSn) {
                    $decoded = ZteOids::parseOidIndex($oid);
                    if ($decoded && $decoded['slot'] > 0 && $decoded['port'] > 0) {
                        $s = $decoded['shelf'] ?: 1;
                        $sl = $decoded['slot'];
                        $p = $decoded['port'];
                        $key = "{$s}/{$sl}/{$p}";
                        $activePortKeys[$key] = true;
                    }
                }
                break;
            }
        }

        // 2. Walk interface tables for port descriptions
        $ifNames = [];
        foreach (ZteOids::PON_IF_NAME_TABLES as $tableOid) {
            $ifNames = $this->snmp()->walkSafe($tableOid);
            if (!empty($ifNames)) {
                Log::debug("SNMP: Port discovery used OID table {$tableOid}, found " . count($ifNames) . " interfaces.");
                break;
            }
        }

        $ifStatus = [];
        foreach (ZteOids::PON_IF_STATUS_TABLES as $statusOid) {
            $ifStatus = $this->snmp()->walkSafe($statusOid);
            if (!empty($ifStatus)) break;
        }

        // 3. Parse discovered interfaces
        foreach ($ifNames as $oid => $desc) {
            $descStr = (string)$desc;

            // Matches: gpon-olt_1/1/1, gpon_1/1/1, GPON 1/1/1, 1/1/1, etc.
            if (preg_match('/(?:gpon[-_]olt_|gpon[_\s]|olt[_\s]|)(\d+)[\/\._](\d+)[\/\._](\d+)/i', $descStr, $matches)) {
                $shelf = (int)$matches[1] ?: 1;
                $slot = (int)$matches[2];
                $portNum = (int)$matches[3];

                if ($slot > 0 && $portNum > 0 && $portNum <= 64) {
                    $parts = explode('.', ltrim($oid, '.'));
                    $index = end($parts);

                    $statusValue = 2; // Default inactive
                    foreach ($ifStatus as $sOid => $sVal) {
                        if (str_ends_with($sOid, ".{$index}")) {
                            $statusValue = (int)$sVal;
                            break;
                        }
                    }

                    $key = "{$shelf}/{$slot}/{$portNum}";
                    $isActive = ($statusValue == 1) || isset($activePortKeys[$key]);

                    $portsMap[$key] = [
                        'shelf'       => $shelf,
                        'slot'        => $slot,
                        'port'        => $portNum,
                        'status'      => $isActive ? 'active' : 'inactive',
                        'description' => "GPON Port {$shelf}/{$slot}/{$portNum}",
                    ];
                }
            }
        }

        // 4. Fallback: If ifTable returned nothing or only partial, generate standard ports for detected/default slots
        if (empty($portsMap)) {
            // Find detected slots from active ONUs, or default to Slot 1
            $slots = [];
            foreach (array_keys($activePortKeys) as $key) {
                $parts = explode('/', $key);
                $slots[(int)$parts[1]] = (int)$parts[0];
            }
            if (empty($slots)) {
                $slots[1] = 1; // Default Slot 1
            }

            foreach ($slots as $slot => $shelf) {
                for ($p = 1; $p <= 16; $p++) {
                    $key = "{$shelf}/{$slot}/{$p}";
                    $isActive = isset($activePortKeys[$key]);
                    $portsMap[$key] = [
                        'shelf'       => $shelf ?: 1,
                        'slot'        => $slot,
                        'port'        => $p,
                        'status'      => $isActive ? 'active' : 'inactive',
                        'description' => "GPON Port {$shelf}/{$slot}/{$p}",
                    ];
                }
            }
        }

        return array_values($portsMap);
    }

    // ═════════════════════════════════════════════════════
    // ONU LISTING PER PORT (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Get all registered ONUs on a specific PON port via SNMP.
     * Returns: index, onu_id, sn, name, type, status, signal
     */
    public function getOnusOnPort(int $shelf, int $slot, int $port): array
    {
        $shelf = $shelf ?: 1;
        Log::info("SNMP: Fetching ONUs on port {$shelf}/{$slot}/{$port} for OLT: {$this->olt->name}");

        // 1. Walk ONU Serial Numbers with multi-table fallback
        $allSns = [];
        foreach (ZteOids::ONU_SN_TABLES as $tableOid) {
            $allSns = $this->snmp()->walkSafe($tableOid);
            if (!empty($allSns)) {
                Log::debug("SNMP: ONU SN walk succeeded on table {$tableOid} (" . count($allSns) . " items)");
                break;
            }
        }

        if (empty($allSns)) {
            Log::warning("SNMP: No ONU Serial numbers found across all tables for {$this->olt->ip_address}");
            return [];
        }

        // 2. Walk ONU Status with multi-table fallback
        $allStatus = [];
        foreach (ZteOids::ONU_STATUS_TABLES as $tableOid) {
            $allStatus = $this->snmp()->walkSafe($tableOid);
            if (!empty($allStatus)) break;
        }

        // 3. Walk ONU Names with multi-table fallback
        $allNames = [];
        foreach (ZteOids::ONU_NAME_TABLES as $tableOid) {
            $allNames = $this->snmp()->walkSafe($tableOid);
            if (!empty($allNames)) break;
        }

        // 4. Walk ONU Types
        $allTypes = [];
        foreach (ZteOids::ONU_TYPE_TABLES as $tableOid) {
            $allTypes = $this->snmp()->walkSafe($tableOid);
            if (!empty($allTypes)) break;
        }

        // 5. Walk RX Optical Power with multi-table fallback
        $allSignals = [];
        foreach (ZteOids::RX_POWER_TABLES as $tableOid) {
            $allSignals = $this->snmp()->walkSafe($tableOid);
            if (!empty($allSignals)) break;
        }

        // 6. Filter & decode ONUs for this specific port
        $onus = [];
        foreach ($allSns as $oid => $rawSn) {
            $decoded = ZteOids::parseOidIndex($oid);
            if (!$decoded) continue;

            // Check if matches requested shelf, slot, port
            if ($decoded['slot'] != $slot || $decoded['port'] != $port) {
                continue;
            }
            if ($decoded['shelf'] != $shelf && $shelf > 1) {
                continue;
            }

            $onuId = $decoded['onu_id'];
            $sn = ZteOids::parseSn($rawSn);

            // Find Status
            $statusStr = 'online';
            foreach ($allStatus as $sOid => $sVal) {
                $sDecoded = ZteOids::parseOidIndex($sOid);
                if ($sDecoded && $sDecoded['slot'] == $slot && $sDecoded['port'] == $port && $sDecoded['onu_id'] == $onuId) {
                    $statusStr = ZteOids::parseOnuStatus($sVal);
                    break;
                }
            }

            // Find Name
            $name = "ONU {$onuId}";
            foreach ($allNames as $nOid => $nVal) {
                $nDecoded = ZteOids::parseOidIndex($nOid);
                if ($nDecoded && $nDecoded['slot'] == $slot && $nDecoded['port'] == $port && $nDecoded['onu_id'] == $onuId) {
                    $cleanedName = trim((string)$nVal, "\"'\0\t\n\r ");
                    if (!empty($cleanedName) && $cleanedName !== 'N/A') {
                        $name = $cleanedName;
                    }
                    break;
                }
            }

            // Find Type
            $type = 'ZTE-ONU';
            foreach ($allTypes as $tOid => $tVal) {
                $tDecoded = ZteOids::parseOidIndex($tOid);
                if ($tDecoded && $tDecoded['slot'] == $slot && $tDecoded['port'] == $port && $tDecoded['onu_id'] == $onuId) {
                    $cleanedType = trim((string)$tVal, "\"'\0\t\n\r ");
                    if (!empty($cleanedType) && $cleanedType !== 'N/A') {
                        $type = $cleanedType;
                    }
                    break;
                }
            }

            // Find Signal
            $signal = 'N/A';
            foreach ($allSignals as $pOid => $pVal) {
                $pDecoded = ZteOids::parseOidIndex($pOid);
                if ($pDecoded && $pDecoded['slot'] == $slot && $pDecoded['port'] == $port && $pDecoded['onu_id'] == $onuId) {
                    $dbm = ZteOids::parseSignalToDbm($pVal);
                    if ($dbm !== null) {
                        $signal = $dbm;
                    }
                    break;
                }
            }

            $onus[] = [
                'index'   => "{$shelf}.{$slot}.{$port}.{$onuId}",
                'onu_id'  => $onuId,
                'sn'      => $sn,
                'name'    => $name,
                'type'    => $type,
                'status'  => $statusStr,
                'reason'  => $statusStr,
                'signal'  => ($statusStr === 'online' || $statusStr === 'working') ? $signal : 'LOST',
            ];
        }

        Log::info("SNMP: Port {$shelf}/{$slot}/{$port} returned " . count($onus) . " ONUs.");
        return $onus;
    }

    // ═════════════════════════════════════════════════════
    // UNCONFIGURED ONU DISCOVERY (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Scan for unconfigured (unregistered) ONUs on the OLT.
     */
    public function scanUnconfiguredOnus(): array
    {
        $results = [];
        foreach (ZteOids::UNCFG_ONU_TABLES as $tableOid) {
            $data = $this->snmp()->walkSafe($tableOid);
            if (!empty($data)) {
                $results = $data;
                Log::debug("SNMP: Unconfigured ONU scan succeeded on table {$tableOid} (" . count($data) . " items)");
                break;
            }
        }

        $onus = [];
        foreach ($results as $oid => $sn) {
            $decoded = ZteOids::parseOidIndex($oid);
            $unconfigId = 1;
            if ($decoded) {
                $shelf = $decoded['shelf'] ?: 1;
                $slot = $decoded['slot'] ?: 1;
                $port = $decoded['port'] ?: 1;
                $unconfigId = $decoded['onu_id'] ?: 1;
            } else {
                $parts = explode('.', ltrim($oid, '.'));
                $unconfigId = array_pop($parts) ?: 1;
                $index = (int)(array_pop($parts) ?: 0);

                $shelf = ($index >> 24) & 0xFF;
                $slot = ($index >> 16) & 0xFF;
                $port = ($index >> 8) & 0xFF;
            }

            $parsedSn = ZteOids::parseSn($sn);
            if (empty($parsedSn) || strlen($parsedSn) < 6) continue;

            $onus[] = [
                'sn'         => $parsedSn,
                'shelf'      => $shelf ?: 1,
                'slot'       => $slot ?: 1,
                'port'       => $port ?: 1,
                'full_index' => "." . ($shelf ?: 1) . ".{$slot}.{$port}.{$unconfigId}",
                'oid'        => $oid,
                'type'       => 'ZTE-ONU',
            ];
        }

        return $onus;
    }

    // ═════════════════════════════════════════════════════
    // BULK POLLING FOR NOC (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Bulk-walk all ONU data for the entire OLT in a single pass.
     */
    public function bulkWalkAllOnus(): array
    {
        // 1. Walk SNs
        $snMap = [];
        foreach (ZteOids::ONU_SN_TABLES as $tableOid) {
            $snMap = $this->snmp()->walkSafe($tableOid);
            if (!empty($snMap)) break;
        }

        // 2. Walk Statuses
        $statusMap = [];
        foreach (ZteOids::ONU_STATUS_TABLES as $tableOid) {
            $statusMap = $this->snmp()->walkSafe($tableOid);
            if (!empty($statusMap)) break;
        }

        // 3. Walk Signals
        $rxMap = [];
        foreach (ZteOids::RX_POWER_TABLES as $tableOid) {
            $rxMap = $this->snmp()->walkSafe($tableOid);
            if (!empty($rxMap)) break;
        }

        return [
            'sns'      => $snMap,
            'statuses' => $statusMap,
            'signals'  => $rxMap,
        ];
    }

    /**
     * Match a customer's ONU data in the bulk SNMP maps.
     */
    public function matchCustomerInBulkData(array $bulkData, ?string $index, ?string $sn): array
    {
        $result = ['rx_power' => null, 'status' => 'unknown', 'real_index' => null];

        // Parse customer's index if given (e.g. 1/1/7:5 or 1.1.7.5)
        $targetShelf = null;
        $targetSlot = null;
        $targetPort = null;
        $targetOnuId = null;

        if ($index) {
            $clean = str_replace([':', '/'], '.', trim($index, '.'));
            $parts = explode('.', $clean);
            if (count($parts) >= 4) {
                $targetShelf = (int)$parts[0];
                $targetSlot  = (int)$parts[1];
                $targetPort  = (int)$parts[2];
                $targetOnuId = (int)$parts[3];
            }
        }

        // 1. Match by SN if available
        if ($sn) {
            $targetSn = strtoupper(trim($sn));
            foreach ($bulkData['sns'] as $oid => $rawSn) {
                $parsedSn = ZteOids::parseSn($rawSn);
                if (strtoupper($parsedSn) === $targetSn) {
                    $decoded = ZteOids::parseOidIndex($oid);
                    if ($decoded) {
                        $targetShelf = $decoded['shelf'];
                        $targetSlot  = $decoded['slot'];
                        $targetPort  = $decoded['port'];
                        $targetOnuId = $decoded['onu_id'];
                        $result['real_index'] = "{$targetShelf}/{$targetSlot}/{$targetPort}:{$targetOnuId}";
                        break;
                    }
                }
            }
        }

        // 2. Extract Signal & Status using decoded target coordinates
        if ($targetSlot !== null && $targetPort !== null && $targetOnuId !== null) {
            // Find Signal
            foreach ($bulkData['signals'] as $pOid => $pVal) {
                $pDec = ZteOids::parseOidIndex($pOid);
                if ($pDec && $pDec['slot'] == $targetSlot && $pDec['port'] == $targetPort && $pDec['onu_id'] == $targetOnuId) {
                    $dbm = ZteOids::parseSignalToDbm($pVal);
                    if ($dbm !== null) {
                        $result['rx_power'] = $dbm;
                        break;
                    }
                }
            }

            // Find Status
            foreach ($bulkData['statuses'] as $sOid => $sVal) {
                $sDec = ZteOids::parseOidIndex($sOid);
                if ($sDec && $sDec['slot'] == $targetSlot && $sDec['port'] == $targetPort && $sDec['onu_id'] == $targetOnuId) {
                    $result['status'] = ZteOids::parseOnuStatus($sVal);
                    break;
                }
            }
        }

        return $result;
    }

    // ═════════════════════════════════════════════════════
    // ONU REBOOT VIA SNMP (Write Operation)
    // ═════════════════════════════════════════════════════

    /**
     * Reboot an ONU via SNMP SET.
     */
    public function rebootOnu(string $index): bool
    {
        try {
            $parts = explode('.', ltrim($index, '.'));
            if (count($parts) < 4) return false;

            $intIndex = ZteOids::onuIndex((int)$parts[0], (int)$parts[1], (int)$parts[2], (int)$parts[3]);

            $writeSnmp = new SnmpService(
                $this->olt->ip_address,
                $this->olt->snmp_write_community ?: 'private',
                (int)($this->olt->snmp_port ?: 161),
                (int)($this->olt->snmp_version ?: 2)
            );

            foreach (ZteOids::ONU_ADMIN_OP_OIDS as $oid) {
                try {
                    $writeSnmp->set($oid . '.' . $intIndex, 1);
                    return true;
                } catch (\Exception $e) {
                    continue;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to reboot ONU via SNMP: " . $e->getMessage());
            return false;
        }
    }

    // ═════════════════════════════════════════════════════
    // PROVISIONING WRITE OPERATIONS (Telnet CLI)
    // ═════════════════════════════════════════════════════

    public function provisionOnu(int $shelf, int $slot, int $port, string $sn, string $onuType, int $vlan = 100, string $description = 'NextLink-Customer'): string|false
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->provisionOnu($shelf, $slot, $port, $sn, $onuType, $vlan, $description);
    }

    public function deprovisionOnu(int $shelf, int $slot, int $port, int $onuId): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->deleteOnu($shelf, $slot, $port, $onuId);
    }

    public function suspendOnu(int $shelf, int $slot, int $port, int $onuId): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->suspendOnu($shelf, $slot, $port, $onuId);
    }

    public function resumeOnu(int $shelf, int $slot, int $port, int $onuId): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->resumeOnu($shelf, $slot, $port, $onuId);
    }

    public function updateOnuProfile(int $shelf, int $slot, int $port, int $onuId, $package): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->updateOnuProfile($shelf, $slot, $port, $onuId, $package);
    }
}
