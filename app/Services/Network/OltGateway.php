<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

/**
 * OLT Gateway — Pure SNMP Communication Layer.
 * 
 * Single entry point for all OLT operations.
 * All read operations use SNMP exclusively.
 * Write operations (provisioning) delegate to ZteOltProvisioningService via Telnet.
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
    protected function snmp(): SnmpService
    {
        if (!$this->snmp) {
            $this->snmp = SnmpService::fromOlt($this->olt);
        }
        return $this->snmp;
    }

    // ═════════════════════════════════════════════════════
    // CONNECTION TESTING
    // ═════════════════════════════════════════════════════

    /**
     * Test SNMP connectivity to the OLT.
     */
    public function testSnmpConnection(): array
    {
        try {
            $name = $this->snmp()->get(ZteOids::SYS_NAME);
            $descr = $this->snmp()->get(ZteOids::SYS_DESCR);

            return [
                'status' => true,
                'message' => 'SNMP Connected successfully',
                'device_name' => (string) $name,
                'description' => (string) $descr,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "SNMP Query Failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Telnet connectivity to the OLT.
     */
    public function testTelnetConnection(): array
    {
        try {
            $service = new ZteOltProvisioningService($this->olt);
            $service->testConnection();
            return ['success' => true, 'message' => 'Telnet Connection Successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ═════════════════════════════════════════════════════
    // OLT SYSTEM STATUS (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Get OLT hardware status: uptime, CPU, temperature, online status.
     */
    public function getOltStatus(): array
    {
        // 1. Check if OLT is reachable via sysUpTime
        $rawUptime = $this->snmp()->getSafe(ZteOids::SYS_UPTIME);

        if (!$rawUptime) {
            return ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
        }

        $uptime = ZteOids::formatUptime((string) $rawUptime);

        // 2. CPU (try multiple OIDs)
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

        // 3. Temperature (try multiple OIDs)
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
     */
    public function discoverPonPorts(): array
    {
        // Try ZTE-specific OIDs first
        $ifNames = $this->snmp()->walkSafe(ZteOids::C300_PON_IF_NAME);
        $ifStatus = $this->snmp()->walkSafe(ZteOids::C300_PON_IF_STATUS);

        // Fallback to standard MIB
        if (empty($ifNames)) {
            $ifNames = $this->snmp()->walkSafe(ZteOids::STD_IF_NAME);
            $ifStatus = $this->snmp()->walkSafe(ZteOids::STD_IF_STATUS);
        }

        $ports = [];
        foreach ($ifNames as $oid => $desc) {
            if (preg_match('/gpon-olt_(\d+)\/(\d+)\/(\d+)/i', $desc, $matches)) {
                $shelf = $matches[1];
                $slot = $matches[2];
                $portNum = $matches[3];

                $parts = explode('.', $oid);
                $index = end($parts);

                $statusValue = 2; // Default inactive
                foreach ($ifStatus as $sOid => $sVal) {
                    if (str_ends_with($sOid, ".{$index}")) {
                        $statusValue = $sVal;
                        break;
                    }
                }

                $ports[] = [
                    'shelf'       => $shelf,
                    'slot'        => $slot,
                    'port'        => $portNum,
                    'status'      => ($statusValue == 1) ? 'active' : 'inactive',
                    'description' => "GPON Port {$shelf}/{$slot}/{$portNum} ({$desc})",
                ];
            }
        }

        return $ports;
    }

    // ═════════════════════════════════════════════════════
    // ONU LISTING PER PORT (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Get all ONUs on a specific PON port via SNMP.
     * Returns: index, onu_id, sn, name, status, signal
     */
    public function getOnusOnPort(int $shelf, int $slot, int $port): array
    {
        $portIdx = ZteOids::portIndex($shelf ?: 1, $slot, $port);
        $searchPrefix = ".{$shelf}.{$slot}.{$port}.";

        Log::info("SNMP: Fetching ONUs on {$shelf}/{$slot}/{$port} (portIdx: {$portIdx})");

        // 1. Walk ONU Serial Numbers (entire OLT, then filter by port)
        $allSns = $this->snmp()->walkSafe(ZteOids::C300_ONU_SN);
        Log::debug("SNMP: Found " . count($allSns) . " total ONU SNs");

        // 2. Walk ONU Status
        $allStatus = $this->snmp()->walkSafe(ZteOids::C300_ONU_REG_STATUS);

        // 3. Walk ONU Names/Descriptions (try C300, then Titan)
        $allNames = $this->snmp()->walkSafe(ZteOids::C300_ONU_NAME);
        if (empty($allNames)) {
            $allNames = $this->snmp()->walkSafe(ZteOids::TITAN_ONU_NAME);
        }

        // 4. Walk RX Power (try C300, then Titan)
        $allSignals = $this->snmp()->walkSafe(ZteOids::C300_RX_POWER);
        if (empty($allSignals)) {
            $allSignals = $this->snmp()->walkSafe(ZteOids::TITAN_RX_POWER);
        }

        // 5. Filter and merge data for this specific port
        $onus = [];
        foreach ($allSns as $oid => $sn) {
            // Check if this ONU belongs to our target port
            // OID format: ...5.{portIdx}.{onuId} OR ...5.{shelf}.{slot}.{port}.{onuId}
            if (!$this->oidMatchesPort($oid, $portIdx, $searchPrefix)) {
                continue;
            }

            $parts = explode('.', $oid);
            $onuId = end($parts);

            // Build search keys
            $searchKeys = [
                $searchPrefix . $onuId,
                '.' . $portIdx . '.' . $onuId,
                (string) ZteOids::onuIndex($shelf ?: 1, $slot, $port, (int)$onuId),
            ];

            // Find status
            $statusStr = 'unknown';
            foreach ($allStatus as $sOid => $sVal) {
                foreach ($searchKeys as $key) {
                    if (str_ends_with($sOid, $key) || str_ends_with($sOid, ".{$onuId}")) {
                        $statusStr = ZteOids::parseOnuStatus((int)$sVal);
                        break 2;
                    }
                }
            }

            // Find name/description
            $name = "ONU {$onuId}";
            foreach ($allNames as $nOid => $nVal) {
                foreach ($searchKeys as $key) {
                    if (str_ends_with($nOid, $key) || str_ends_with($nOid, ".{$onuId}")) {
                        if ($nVal && $nVal !== 'N/A' && !is_numeric($nVal)) {
                            $name = trim((string)$nVal);
                        }
                        break 2;
                    }
                }
            }

            // Find signal
            $signal = 'N/A';
            foreach ($allSignals as $pOid => $pVal) {
                foreach ($searchKeys as $key) {
                    if (str_ends_with($pOid, $key) || str_ends_with($pOid, ".{$onuId}")) {
                        $dbm = ZteOids::parseSignalToDbm($pVal);
                        if ($dbm !== null) {
                            $signal = $dbm;
                        }
                        break 2;
                    }
                }
            }

            $onus[] = [
                'index'   => "{$shelf}.{$slot}.{$port}.{$onuId}",
                'onu_id'  => $onuId,
                'sn'      => ZteOids::parseSn($sn),
                'name'    => $name,
                'type'    => 'ZTE-ONU',
                'status'  => $statusStr,
                'reason'  => $statusStr,
                'signal'  => ($statusStr === 'online') ? $signal : 'LOST',
            ];
        }

        Log::info("SNMP: Found " . count($onus) . " ONUs on port {$shelf}/{$slot}/{$port}");
        return $onus;
    }

    // ═════════════════════════════════════════════════════
    // SINGLE ONU OPERATIONS (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Get RX signal for a single ONU by index (e.g. "1.1.7.5")
     */
    public function getOnuSignal(string $index): ?float
    {
        $parts = explode('.', ltrim($index, '.'));
        if (count($parts) < 4) return null;

        $intIndex = ZteOids::onuIndex((int)$parts[0], (int)$parts[1], (int)$parts[2], (int)$parts[3]);

        // Try C300 OID
        $val = $this->snmp()->getSafe(ZteOids::C300_RX_POWER . '.' . $intIndex);
        if ($val !== null) {
            $dbm = ZteOids::parseSignalToDbm($val);
            if ($dbm !== null) return $dbm;
        }

        // Try Titan OID
        $val = $this->snmp()->getSafe(ZteOids::TITAN_RX_POWER . '.' . $intIndex);
        if ($val !== null) {
            return ZteOids::parseSignalToDbm($val);
        }

        // Try with dot-notation index
        $dotIndex = ".{$parts[0]}.{$parts[1]}.{$parts[2]}.{$parts[3]}";
        $val = $this->snmp()->getSafe(ZteOids::C300_RX_POWER . $dotIndex);
        if ($val !== null) {
            return ZteOids::parseSignalToDbm($val);
        }

        return null;
    }

    /**
     * Get status for a single ONU by index.
     */
    public function getOnuStatus(string $index): string
    {
        $parts = explode('.', ltrim($index, '.'));
        if (count($parts) < 4) return 'unknown';

        $intIndex = ZteOids::onuIndex((int)$parts[0], (int)$parts[1], (int)$parts[2], (int)$parts[3]);

        $val = $this->snmp()->getSafe(ZteOids::C300_ONU_REG_STATUS . '.' . $intIndex);
        if ($val !== null) {
            return ZteOids::parseOnuStatus((int)$val);
        }

        // Try dot-notation
        $dotIndex = ".{$parts[0]}.{$parts[1]}.{$parts[2]}.{$parts[3]}";
        $val = $this->snmp()->getSafe(ZteOids::C300_ONU_REG_STATUS . $dotIndex);
        if ($val !== null) {
            return ZteOids::parseOnuStatus((int)$val);
        }

        return 'unknown';
    }

    /**
     * Find an ONU by its Serial Number across all ports.
     * Returns: index, onu_id, sn, shelf, slot, port or null
     */
    public function findOnuBySn(string $sn): ?array
    {
        $allSns = $this->snmp()->walkSafe(ZteOids::C300_ONU_SN);

        foreach ($allSns as $oid => $rawSn) {
            $parsedSn = ZteOids::parseSn($rawSn);

            if (strtoupper($parsedSn) === strtoupper($sn)) {
                // Parse the OID to extract port/ONU info
                $parts = explode('.', $oid);
                $onuId = array_pop($parts);
                $snmpIndex = array_pop($parts);

                // Decode the index
                $decoded = ZteOids::decodeIndex((int)$snmpIndex);

                return [
                    'sn'      => $parsedSn,
                    'onu_id'  => $onuId,
                    'shelf'   => $decoded['shelf'] ?: 1,
                    'slot'    => $decoded['slot'],
                    'port'    => $decoded['port'],
                    'index'   => "{$decoded['shelf']}/{$decoded['slot']}/{$decoded['port']}:{$onuId}",
                ];
            }
        }

        return null;
    }

    // ═════════════════════════════════════════════════════
    // UNCONFIGURED ONU DISCOVERY (Pure SNMP)
    // ═════════════════════════════════════════════════════

    /**
     * Scan for unconfigured (unregistered) ONUs on the OLT.
     */
    public function scanUnconfiguredOnus(): array
    {
        Log::info("SNMP: Starting unconfigured ONU scan for OLT: {$this->olt->name}");

        $oids = [
            ZteOids::C300_UNCFG_ONU,
            ZteOids::C320_UNCFG_ONU,
            ZteOids::TITAN_UNCFG_ONU,
        ];

        $results = [];
        foreach ($oids as $oid) {
            $data = $this->snmp()->walkSafe($oid);
            if (!empty($data)) {
                $results = array_merge($results, $data);
            }
        }

        $onus = [];
        foreach ($results as $oid => $sn) {
            $parts = explode('.', $oid);
            $unconfigId = array_pop($parts);
            $index = array_pop($parts);

            $shelf = ($index >> 24) & 0xFF;
            $slot = ($index >> 16) & 0xFF;
            $port = ($index >> 8) & 0xFF;
            $shelf = $shelf ?: 1;

            $onus[] = [
                'sn'         => ZteOids::parseSn($sn),
                'shelf'      => $shelf,
                'slot'       => $slot,
                'port'       => $port,
                'full_index' => ".{$shelf}.{$slot}.{$port}.{$unconfigId}",
                'oid'        => $oid,
                'type'       => 'ZTE-ONU',
            ];
        }

        Log::info("SNMP: Found " . count($onus) . " unconfigured ONUs.");
        return $onus;
    }

    // ═════════════════════════════════════════════════════
    // BULK POLLING (Pure SNMP — for NocPollCommand)
    // ═════════════════════════════════════════════════════

    /**
     * Bulk-walk all ONU data for the entire OLT in one go.
     * Returns raw maps that can be efficiently queried per-customer.
     */
    public function bulkWalkAllOnus(): array
    {
        // Walk all SNs
        $snMap = $this->snmp()->walkSafe(ZteOids::C300_ONU_SN);

        // Walk all statuses
        $statusMap = $this->snmp()->walkSafe(ZteOids::C300_ONU_REG_STATUS);

        // Walk RX power (try multiple OIDs)
        $rxMap = [];
        $rxOids = [ZteOids::TITAN_RX_POWER, ZteOids::C300_RX_POWER];
        foreach ($rxOids as $oid) {
            $rxMap = $this->snmp()->walkSafe($oid);
            if (!empty($rxMap)) break;
        }

        return [
            'sns'      => $snMap,
            'statuses' => $statusMap,
            'signals'  => $rxMap,
        ];
    }

    /**
     * Match a customer's ONU index to bulk-walked SNMP data.
     */
    public function matchCustomerInBulkData(array $bulkData, ?string $index, ?string $sn): array
    {
        $result = ['rx_power' => null, 'status' => 'unknown', 'real_index' => null];

        // 1. Try matching by index
        if ($index) {
            $dotIndex = str_replace(['/', ':'], '.', ltrim($index, '.'));
            $parts = explode('.', $dotIndex);

            $searchKeys = [$dotIndex];
            if (count($parts) >= 3) {
                $integerIndex = (string)(((int)$parts[0] << 24) | ((int)$parts[1] << 16) | ((int)$parts[2] << 8) | (isset($parts[3]) ? (int)$parts[3] : 0));
                $searchKeys[] = $integerIndex;
            }

            // Find RX Power
            foreach ($searchKeys as $search) {
                foreach ($bulkData['signals'] as $oid => $val) {
                    if (str_ends_with($oid, $search)) {
                        $result['rx_power'] = ZteOids::parseSignalToDbm($val);
                        break 2;
                    }
                }
            }

            // Find Status
            foreach ($searchKeys as $search) {
                foreach ($bulkData['statuses'] as $oid => $val) {
                    if (str_ends_with($oid, $search)) {
                        $result['status'] = ZteOids::parseOnuStatus((int)$val);
                        break 2;
                    }
                }
            }
        }

        // 2. If no match by index, try matching by SN
        if ($result['rx_power'] === null && $sn) {
            foreach ($bulkData['sns'] as $oid => $rawSn) {
                $parsedSn = ZteOids::parseSn($rawSn);
                if (strtoupper($parsedSn) === strtoupper($sn)) {
                    // Found! Extract the ONU index from the OID
                    $parts = explode('.', $oid);
                    $onuId = array_pop($parts);
                    $snmpIndex = array_pop($parts);
                    $decoded = ZteOids::decodeIndex((int)$snmpIndex);

                    $shelf = $decoded['shelf'] ?: 1;
                    $realIndex = "{$shelf}/{$decoded['slot']}/{$decoded['port']}:{$onuId}";
                    $result['real_index'] = $realIndex;

                    // Now find signal/status using the decoded index
                    $dotKey = ".{$shelf}.{$decoded['slot']}.{$decoded['port']}.{$onuId}";
                    $intKey = (string)((int)$snmpIndex | (int)$onuId);

                    foreach ([$dotKey, $intKey] as $key) {
                        foreach ($bulkData['signals'] as $pOid => $pVal) {
                            if (str_ends_with($pOid, $key)) {
                                $result['rx_power'] = ZteOids::parseSignalToDbm($pVal);
                                break 2;
                            }
                        }
                    }

                    foreach ([$dotKey, $intKey] as $key) {
                        foreach ($bulkData['statuses'] as $sOid => $sVal) {
                            if (str_ends_with($sOid, $key)) {
                                $result['status'] = ZteOids::parseOnuStatus((int)$sVal);
                                break 2;
                            }
                        }
                    }

                    break;
                }
            }
        }

        return $result;
    }

    // ═════════════════════════════════════════════════════
    // ONU WRITE OPERATIONS (SNMP SET)
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
                $this->olt->snmp_write_community,
                $this->olt->snmp_port ?? 161,
                $this->olt->snmp_version ?? 2
            );

            $writeSnmp->set(ZteOids::C300_ONU_ADMIN_OP . '.' . $intIndex, 1);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to reboot ONU via SNMP: " . $e->getMessage());
            return false;
        }
    }

    // ═════════════════════════════════════════════════════
    // PROVISIONING (Delegated to Telnet Service)
    // ═════════════════════════════════════════════════════

    /**
     * Provision (register) an ONU on the OLT.
     * This requires Telnet CLI — SNMP cannot do full provisioning.
     */
    public function provisionOnu(int $shelf, int $slot, int $port, string $sn, string $onuType, int $vlan = 100, string $description = 'NextLink-Customer'): string|false
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->provisionOnu($shelf, $slot, $port, $sn, $onuType, $vlan, $description);
    }

    /**
     * Deprovision (delete) an ONU from the OLT.
     */
    public function deprovisionOnu(int $shelf, int $slot, int $port, int $onuId): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->deleteOnu($shelf, $slot, $port, $onuId);
    }

    /**
     * Suspend an ONU.
     */
    public function suspendOnu(int $shelf, int $slot, int $port, int $onuId): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->suspendOnu($shelf, $slot, $port, $onuId);
    }

    /**
     * Resume an ONU.
     */
    public function resumeOnu(int $shelf, int $slot, int $port, int $onuId): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->resumeOnu($shelf, $slot, $port, $onuId);
    }

    /**
     * Update ONU bandwidth profile.
     */
    public function updateOnuProfile(int $shelf, int $slot, int $port, int $onuId, $package): bool
    {
        $service = new ZteOltProvisioningService($this->olt);
        return $service->updateOnuProfile($shelf, $slot, $port, $onuId, $package);
    }

    // ═════════════════════════════════════════════════════
    // INTERNAL HELPERS
    // ═════════════════════════════════════════════════════

    /**
     * Check if an SNMP OID belongs to a specific port.
     */
    protected function oidMatchesPort(string $oid, int $portIdx, string $dotPrefix): bool
    {
        // Method 1: Integer-based index
        if (str_contains($oid, '.' . $portIdx . '.')) {
            return true;
        }

        // Method 2: Dot-notation (e.g., .1.1.7.)
        if (str_contains($oid, $dotPrefix)) {
            return true;
        }

        return false;
    }
}
