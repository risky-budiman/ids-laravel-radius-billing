<?php

namespace App\Services\Network;

/**
 * Centralized SNMP OID Registry for ZTE OLT devices.
 * 
 * Comprehensive support for:
 * - ZTE C300 / C320 V1.x (Classic Series)
 * - ZTE C300 / C320 V2.x (Enhanced GPON MIB)
 * - ZTE C600 / C650 (Titan Series)
 */
class ZteOids
{
    // ═══════════════════════════════════════════════
    // SYSTEM (Standard MIB-II)
    // ═══════════════════════════════════════════════
    const SYS_UPTIME = '.1.3.6.1.2.1.1.3.0';
    const SYS_NAME   = '.1.3.6.1.2.1.1.5.0';
    const SYS_DESCR  = '.1.3.6.1.2.1.1.1.0';

    // ═══════════════════════════════════════════════
    // ONU SERIAL NUMBER TABLES (Walk to discover ONUs)
    // ═══════════════════════════════════════════════
    const ONU_SN_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.28.1.1.5',         // ZTE C300/C320 V1.x (zxAnGponOnuSerialNum)
        '.1.3.6.1.4.1.3902.1082.500.10.2.2.1.5',    // ZTE C320/C300 V2.x (zxAnGponSrvOnuSn)
        '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.5',      // ZTE Titan C600/C650
        '.1.3.6.1.4.1.3902.1012.3.50.11.2.1.9',     // ZTE Standard GPON ONU SN
    ];

    // ═══════════════════════════════════════════════
    // ONU REGISTRATION / PHASE STATUS TABLES
    // Values: 1=logging, 2=los, 3=syncloss, 4=working/online,
    //         5=dyinggasp, 6=authFailed, 7=offline
    // ═══════════════════════════════════════════════
    const ONU_STATUS_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.28.2.1.4',         // ZTE C300 V1 Phase Status
        '.1.3.6.1.4.1.3902.1082.500.10.2.2.1.8',    // ZTE C320 V2 Phase State
        '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.7',      // ZTE Titan Phase State
        '.1.3.6.1.4.1.3902.1012.3.28.1.1.2',         // ZTE C300 Admin Status
    ];

    // ═══════════════════════════════════════════════
    // ONU NAME / DESCRIPTION TABLES
    // ═══════════════════════════════════════════════
    const ONU_NAME_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.28.1.1.3',         // ZTE C300 V1 Description
        '.1.3.6.1.4.1.3902.1082.500.10.2.2.1.4',    // ZTE C320 V2 Name
        '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.4',      // ZTE Titan Name
    ];

    // ═══════════════════════════════════════════════
    // ONU TYPE / MODEL TABLES
    // ═══════════════════════════════════════════════
    const ONU_TYPE_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.28.1.1.4',         // ZTE C300 V1 Type
        '.1.3.6.1.4.1.3902.1082.500.10.2.2.1.2',    // ZTE C320 V2 Type
    ];

    // ═══════════════════════════════════════════════
    // OPTICAL POWER (RX Signal dBm) TABLES
    // ═══════════════════════════════════════════════
    const RX_POWER_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.10',     // ZTE C300 ONU Rx Power
        '.1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.2',    // ZTE C320 V2 / Titan OLT Rx Power (from ONU)
        '.1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.3',    // ZTE C320 V2 / Titan ONU Rx Power (from OLT)
        '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.14',     // ZTE C300 OLT Rx Power
    ];

    // ═══════════════════════════════════════════════
    // UNCONFIGURED ONU DISCOVERY TABLES
    // ═══════════════════════════════════════════════
    const UNCFG_ONU_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.28.1.1.5',         // ZTE C300 V1
        '.1.3.6.1.4.1.3902.1082.500.10.1.2.1.10',    // ZTE C320 V2
        '.1.3.6.1.4.1.3902.1082.500.10.1.2.1.5',     // ZTE C320 V2 alt
        '.1.3.6.1.4.1.3902.1082.500.1.2.4.1.3.1.5',  // ZTE Titan
    ];

    // ═══════════════════════════════════════════════
    // PON PORT DISCOVERY TABLES
    // ═══════════════════════════════════════════════
    const PON_IF_NAME_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.1.2.1.1.3',        // ZTE C300 GPON Port Description
        '.1.3.6.1.2.1.31.1.1.1.1',                   // Standard ifName
        '.1.3.6.1.2.1.2.2.1.2',                      // Standard ifDescr
    ];

    const PON_IF_STATUS_TABLES = [
        '.1.3.6.1.4.1.3902.1012.3.1.2.1.1.7',        // ZTE C300 Port Status
        '.1.3.6.1.2.1.2.2.1.8',                      // Standard ifOperStatus
    ];

    // ═══════════════════════════════════════════════
    // HARDWARE MONITORING (CPU & Temperature)
    // ═══════════════════════════════════════════════
    const CPU_OIDS = [
        '.1.3.6.1.4.1.3902.1082.10.1.3.1.1.3', // Titan CPU
        '.1.3.6.1.4.1.3902.1012.3.1.3.1.1.3',   // C300 CPU
        '.1.3.6.1.2.1.25.3.3.1.2',               // Global Host Resources CPU
    ];

    const TEMP_OIDS = [
        '.1.3.6.1.4.1.3902.1082.10.1.3.1.1.2', // Titan Temp
        '.1.3.6.1.4.1.3902.1012.3.1.2.1.1.3',   // C300 Temp
    ];

    // ═══════════════════════════════════════════════
    // ONU ADMIN OPERATIONS (SNMP SET - Write)
    // ═══════════════════════════════════════════════
    const ONU_ADMIN_OP_OIDS = [
        '.1.3.6.1.4.1.3902.1012.3.28.1.1.1',
        '.1.3.6.1.4.1.3902.1082.500.10.2.2.1.1',
    ];

    // ═══════════════════════════════════════════════
    // HELPERS & INDEX DECODERS
    // ═══════════════════════════════════════════════

    /**
     * Calculate SNMP port index from shelf/slot/port.
     */
    public static function portIndex(int $shelf, int $slot, int $port): int
    {
        return ($shelf << 24) | ($slot << 16) | ($port << 8);
    }

    /**
     * Calculate SNMP ONU index from shelf/slot/port/onuId.
     */
    public static function onuIndex(int $shelf, int $slot, int $port, int $onuId): int
    {
        return self::portIndex($shelf, $slot, $port) | $onuId;
    }

    /**
     * Decode an OID string into [shelf, slot, port, onu_id].
     * Handles 2-part integer index (.portIdx.onuId), 4-part (.shelf.slot.port.onuId), and 5-part tails.
     */
    public static function parseOidIndex(string $oid): ?array
    {
        $clean = ltrim($oid, '.');
        $parts = explode('.', $clean);
        $count = count($parts);

        if ($count < 2) return null;

        $last = (int) $parts[$count - 1];
        $secondLast = (int) $parts[$count - 2];

        // Format A: [portIndex, onuId] e.g. .268501248.5 or .16843008.5
        if ($secondLast > 1000) {
            $shelf = ($secondLast >> 24) & 0xFF;
            if ($shelf > 15) {
                $shelf = ($shelf & 0x0F);
            }
            $slot = ($secondLast >> 16) & 0xFF;
            $port = ($secondLast >> 8) & 0xFF;

            return [
                'shelf'      => $shelf ?: 1,
                'slot'       => $slot,
                'port'       => $port,
                'onu_id'     => $last,
                'port_index' => $secondLast,
            ];
        }

        // Format B: [shelf, slot, port, onuId] e.g. .1.1.7.5
        if ($count >= 4) {
            $shelf = (int) $parts[$count - 4];
            $slot  = (int) $parts[$count - 3];
            $port  = (int) $parts[$count - 2];
            $onuId = (int) $parts[$count - 1];

            if ($shelf < 32 && $slot < 64 && $port < 64 && $onuId > 0 && $onuId <= 256) {
                return [
                    'shelf'      => $shelf ?: 1,
                    'slot'       => $slot,
                    'port'       => $port,
                    'onu_id'     => $onuId,
                    'port_index' => self::portIndex($shelf ?: 1, $slot, $port),
                ];
            }
        }

        return null;
    }

    /**
     * Parse ONU registration status integer to string.
     */
    public static function parseOnuStatus($value): string
    {
        if ($value === null || $value === '') return 'unknown';

        $val = (int)$value;
        return match ($val) {
            1 => 'logging',
            2 => 'los',
            3 => 'syncloss',
            4 => 'online', // working / ready
            5 => 'dying-gasp',
            6 => 'auth-failed',
            7 => 'offline',
            default => is_string($value) && !is_numeric($value) ? strtolower($value) : 'unknown',
        };
    }

    /**
     * Convert raw SNMP optical power reading to dBm.
     */
    public static function parseSignalToDbm($rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '' || $rawValue === 'N/A') {
            return null;
        }

        $power = (float)$rawValue;

        // Common sentinel values for "no signal" / "offline"
        if ($power == 0 || $power == 65535 || $power == 65535000 || $power >= 2147483640) {
            return null;
        }

        // ZTE unsigned 16-bit negative representation (e.g. 63000..65534)
        if ($power > 30000 && $power < 65535) {
            $power = ($power - 65536);
        }

        // Value in 0.001 dBm (e.g. -20500 or 20500)
        if (abs($power) > 1000) {
            $dbm = $power / 1000;
        } elseif (abs($power) > 100) {
            // Value in 0.01 dBm (e.g. -2050)
            $dbm = $power / 100;
        } elseif (abs($power) > 40) {
            // Value in 0.1 dBm (e.g. -205)
            $dbm = $power / 10;
        } else {
            // Already direct dBm (e.g. -20.5)
            $dbm = $power;
        }

        // Valid GPON optical signal range check (-50 to +10 dBm)
        if ($dbm < -50 || $dbm > 20) {
            return null;
        }

        return round($dbm, 2);
    }

    /**
     * Parse binary / hex / text Serial Number from SNMP response to standard string.
     */
    public static function parseSn($sn): string
    {
        if (empty($sn)) return '';

        // Case 1: 8 raw binary bytes (First 4 = Vendor e.g. ZTEG, last 4 = hex serial)
        if (strlen($sn) === 8) {
            $vendor = substr($sn, 0, 4);
            $hex = bin2hex(substr($sn, 4, 4));
            if (ctype_print($vendor)) {
                return strtoupper($vendor . $hex);
            }
            return strtoupper(bin2hex($sn));
        }

        // Case 2: 16-character hex representation of 8 bytes
        if (strlen($sn) === 16 && ctype_xdigit($sn)) {
            $vendorHex = substr($sn, 0, 8);
            $vendor = @hex2bin($vendorHex);
            if ($vendor && ctype_alnum($vendor)) {
                return strtoupper($vendor . substr($sn, 8, 8));
            }
            return strtoupper($sn);
        }

        // Case 3: Already clean string like ZTEGC1234567, HWTC12345678, etc.
        $clean = trim($sn, "\"'\0\t\n\r ");
        if (preg_match('/^([A-Z]{4})([A-Z0-9]{8,12})$/i', $clean, $m)) {
            return strtoupper($m[1] . $m[2]);
        }

        if (ctype_print($clean) && strlen($clean) >= 6) {
            return $clean;
        }

        return strtoupper(bin2hex($sn));
    }

    /**
     * Format SNMP Timeticks to human readable string (e.g. "5d 04:12:30").
     */
    public static function formatUptime($ticks): string
    {
        if (preg_match('/(\d+):(\d+):(\d+)/', (string) $ticks)) {
            if (preg_match('/\)\s*(.*)/', (string) $ticks, $m)) {
                return $m[1];
            }
            return (string) $ticks;
        }

        $ticksVal = preg_replace('/[^0-9]/', '', (string) $ticks);
        if (!$ticksVal || !is_numeric($ticksVal)) return (string) $ticks;

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
