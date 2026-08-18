<?php

namespace App\Services\Network;

/**
 * Centralized SNMP OID Registry for ZTE OLT devices.
 * 
 * Supports:
 * - ZTE C300/C320 (Classic Series)
 * - ZTE C600/C650 (Titan Series)
 * 
 * Index Format for Port-specific walks:
 *   portIndex = (shelf << 24) | (slot << 16) | (port << 8)
 *   onuIndex  = portIndex | onuId
 */
class ZteOids
{
    // ═══════════════════════════════════════════════
    // SYSTEM (Standard MIB-II, works on all devices)
    // ═══════════════════════════════════════════════
    const SYS_UPTIME = '.1.3.6.1.2.1.1.3.0';
    const SYS_NAME   = '.1.3.6.1.2.1.1.5.0';
    const SYS_DESCR  = '.1.3.6.1.2.1.1.1.0';

    // ═══════════════════════════════════════════════
    // ONU SERIAL NUMBER (Walk to get all ONUs)
    // ═══════════════════════════════════════════════
    const C300_ONU_SN = '.1.3.6.1.4.1.3902.1012.3.28.1.1.5';

    // ═══════════════════════════════════════════════
    // ONU REGISTRATION STATUS
    // Values: 1=logging, 2=los, 3=syncloss, 4=online,
    //         5=dyinggasp, 6=authFailed, 7=offline
    // ═══════════════════════════════════════════════
    const C300_ONU_REG_STATUS = '.1.3.6.1.4.1.3902.1012.3.28.1.1.2';

    // ═══════════════════════════════════════════════
    // ONU NAME / DESCRIPTION
    // ═══════════════════════════════════════════════
    const C300_ONU_NAME  = '.1.3.6.1.4.1.3902.1012.3.28.1.1.3';
    const TITAN_ONU_NAME = '.1.3.6.1.4.1.3902.1082.10.1.2.4.1.4';

    // ═══════════════════════════════════════════════
    // OPTICAL POWER (RX Signal dBm)
    // ═══════════════════════════════════════════════
    const C300_RX_POWER  = '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.10';
    const TITAN_RX_POWER = '.1.3.6.1.4.1.3902.1082.500.1.2.4.2.1.2';

    // ═══════════════════════════════════════════════
    // UNCONFIGURED ONU DISCOVERY
    // ═══════════════════════════════════════════════
    const C300_UNCFG_ONU  = '.1.3.6.1.4.1.3902.1012.3.28.1.1.5';
    const C320_UNCFG_ONU  = '.1.3.6.1.4.1.3902.1082.500.10.1.2.1.10';
    const TITAN_UNCFG_ONU = '.1.3.6.1.4.1.3902.1082.500.1.2.4.1.3.1.5';

    // ═══════════════════════════════════════════════
    // PON PORT DISCOVERY (Interface Names & Status)
    // ═══════════════════════════════════════════════
    const C300_PON_IF_NAME   = '.1.3.6.1.4.1.3902.1012.3.1.2.1.1.3';
    const C300_PON_IF_STATUS = '.1.3.6.1.4.1.3902.1012.3.1.2.1.1.7';
    const STD_IF_NAME        = '.1.3.6.1.2.1.31.1.1.1.1';
    const STD_IF_STATUS      = '.1.3.6.1.2.1.2.2.1.8';

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
    const C300_ONU_ADMIN_OP = '.1.3.6.1.4.1.3902.1012.3.28.1.1.1';

    // ═══════════════════════════════════════════════
    // HELPERS
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
     * Decode integer SNMP index back to shelf/slot/port/onuId.
     */
    public static function decodeIndex(int $index): array
    {
        return [
            'shelf'  => ($index >> 24) & 0xFF,
            'slot'   => ($index >> 16) & 0xFF,
            'port'   => ($index >> 8) & 0xFF,
            'onu_id' => $index & 0xFF,
        ];
    }

    /**
     * Parse ONU registration status integer to string.
     */
    public static function parseOnuStatus(int $value): string
    {
        return match ($value) {
            1 => 'logging',
            2 => 'los',
            3 => 'syncloss',
            4 => 'online',
            5 => 'dying-gasp',
            6 => 'auth-failed',
            7 => 'offline',
            default => 'unknown',
        };
    }

    /**
     * Convert raw SNMP signal value to dBm.
     */
    public static function parseSignalToDbm($rawValue): ?float
    {
        $power = (float) $rawValue;

        if ($power == 0 || $power == 65535 || $power == 65535000) {
            return null;
        }

        if (abs($power) > 1000) {
            return round($power / 1000, 2);
        }

        if ($power > 30000) {
            return round(($power - 65536) * 0.1, 2);
        }

        return round($power * 0.1, 2);
    }

    /**
     * Parse binary SN from SNMP response to readable string.
     */
    public static function parseSn($sn): string
    {
        if (strlen($sn) == 8) {
            $vendor = substr($sn, 0, 4);
            $hex = bin2hex(substr($sn, 4, 4));
            return $vendor . strtoupper($hex);
        }

        if (ctype_alnum($sn) && strlen($sn) >= 12) {
            return strtoupper($sn);
        }

        return strtoupper(bin2hex($sn));
    }

    /**
     * Format SNMP Timeticks to human readable string.
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
