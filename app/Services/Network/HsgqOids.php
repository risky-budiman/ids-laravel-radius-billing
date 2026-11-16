<?php

namespace App\Services\Network;

/**
 * Centralized SNMP OID Registry for HSGQ EPON / GPON OLT devices.
 * 
 * Supports HSGQ E04ID, E04L, E08L, G02ID, and generic HSGQ chassis
 * based on enterprise branch .1.3.6.1.4.1.50224
 */
class HsgqOids
{
    // ═══════════════════════════════════════════════
    // SYSTEM (Standard MIB-II & HSGQ Private)
    // ═══════════════════════════════════════════════
    const SYS_UPTIME   = '.1.3.6.1.2.1.1.3.0';
    const SYS_NAME     = '.1.3.6.1.2.1.1.5.0';
    const SYS_DESCR    = '.1.3.6.1.2.1.1.1.0';
    const SYS_LOCATION = '.1.3.6.1.2.1.1.6.0';
    const SYS_CONTACT  = '.1.3.6.1.2.1.1.4.0';
    const SYS_OBJECT_ID = '.1.3.6.1.2.1.1.2.0';

    // HSGQ Private System Info (.1.3.6.1.4.1.50224.3.1.1)
    const HSGQ_DEVICE_TIME = '.1.3.6.1.4.1.50224.3.1.1.2.0';
    const HSGQ_UPTIME_SEC  = '.1.3.6.1.4.1.50224.3.1.1.3.0';
    const HSGQ_HARDWARE_VER = '.1.3.6.1.4.1.50224.3.1.1.5.0';
    const HSGQ_SOFTWARE_VER = '.1.3.6.1.4.1.50224.3.1.1.6.0';
    const HSGQ_PON_COUNT   = '.1.3.6.1.4.1.50224.3.1.1.8.0'; // e.g. 4
    const HSGQ_GE_COUNT    = '.1.3.6.1.4.1.50224.3.1.1.9.0'; // e.g. 8
    const HSGQ_CPU_USAGE   = '.1.3.6.1.4.1.50224.3.1.1.17.0'; // e.g. 11 (%)
    const HSGQ_TEMPERATURE = '.1.3.6.1.4.1.50224.3.1.1.18.0'; // e.g. 35 (°C)

    // ═══════════════════════════════════════════════
    // PORT TABLES (.1.3.6.1.4.1.50224.3.2.1.1)
    // Indexes: 16777472 (PON1), 16777728 (PON2), etc.
    // ═══════════════════════════════════════════════
    const PORT_NAME_TABLE       = '.1.3.6.1.4.1.50224.3.2.1.1.2'; // PON1, PON2, GE1, etc.
    const PORT_DESC_TABLE       = '.1.3.6.1.4.1.50224.3.2.1.1.3';
    const PORT_TYPE_TABLE       = '.1.3.6.1.4.1.50224.3.2.1.1.4'; // 10=PON, 7=GE
    const PORT_SPEED_TABLE      = '.1.3.6.1.4.1.50224.3.2.1.1.5'; // 1000 Mbps
    const PORT_ADMIN_STAT_TABLE = '.1.3.6.1.4.1.50224.3.2.1.1.6'; // 1=Up, 2=Down
    const PORT_OPER_STAT_TABLE  = '.1.3.6.1.4.1.50224.3.2.1.1.7'; // 1=Up, 2=Down

    // ═══════════════════════════════════════════════
    // ONU TABLES — dipetakan dari snmpwalk HSGQ-E04ID (firmware IR_V3.3.3ID_Rel) & dicocokkan dengan
    // "show onu-info" / "show optical-diag" di CLI pada 238 ONU (MAC 100% cocok, Rx selisih <= 0,05 dBm).
    // Indeks baris = ifIndex ONU = basis port PON (16777472 + 256 × (pon-1)) + ID ONU.
    // ═══════════════════════════════════════════════
    const ONU_NAME_TABLE     = '.1.3.6.1.4.1.50224.3.3.2.1.2';  // nama ONU (mis. "20391582228")
    const ONU_MAC_TABLE      = '.1.3.6.1.4.1.50224.3.3.2.1.7';  // MAC ONU (6 byte) — EPON memakai MAC sebagai "SN"
    const ONU_DISTANCE_TABLE = '.1.3.6.1.4.1.50224.3.3.2.1.15'; // jarak (meter)
    // Optik: indeks <ifIndex ONU>.0.0 (baris <ifIndex port>.65535.65535 = transceiver port PON)
    const ONU_RX_POWER_TABLE = '.1.3.6.1.4.1.50224.3.3.3.1.4';  // Rx ONU, satuan 0,01 dBm (-1563 = -15,63 dBm)
    const ONU_TX_POWER_TABLE = '.1.3.6.1.4.1.50224.3.3.3.1.5';  // Tx ONU, 0,01 dBm
    const ONU_BIAS_TABLE     = '.1.3.6.1.4.1.50224.3.3.3.1.6';  // bias laser, 0,01 mA
    const ONU_VOLTAGE_TABLE  = '.1.3.6.1.4.1.50224.3.3.3.1.7';  // tegangan, 0,01 V
    const ONU_TEMP_TABLE     = '.1.3.6.1.4.1.50224.3.3.3.1.8';  // suhu, 0,01 °C
    const PON_INDEX_BASE     = 16777472;
    const OPTICAL_NA         = -2147483648;                     // nilai "tidak tersedia"

    // ═══════════════════════════════════════════════
    // TRAFFIC COUNTERS (.1.3.6.1.4.1.50224.3.10.1.1)
    // ═══════════════════════════════════════════════
    const PORT_RX_BYTES_TABLE   = '.1.3.6.1.4.1.50224.3.10.1.1.4'; // InOctets (64-bit)
    const PORT_TX_BYTES_TABLE   = '.1.3.6.1.4.1.50224.3.10.1.1.26'; // OutOctets (64-bit)
    const PORT_RX_PACKETS_TABLE = '.1.3.6.1.4.1.50224.3.10.1.1.5';
    const PORT_TX_PACKETS_TABLE = '.1.3.6.1.4.1.50224.3.10.1.1.27';

    /**
     * Format raw uptime string to human readable format.
     */
    public static function formatUptime(string $rawUptime): string
    {
        $clean = trim($rawUptime);
        if (preg_match('/(?:(\d+)\s+days?,\s*)?(\d+):(\d+):(\d+)/i', $clean, $matches)) {
            $days = !empty($matches[1]) ? (int)$matches[1] : 0;
            $hours = (int)$matches[2];
            $minutes = (int)$matches[3];
            $parts = [];
            if ($days > 0) $parts[] = "{$days}d";
            if ($hours > 0) $parts[] = "{$hours}h";
            $parts[] = "{$minutes}m";
            return implode(' ', $parts);
        }

        if (is_numeric($clean)) {
            $totalSeconds = (int) floor((int)$clean / 100);
            $days = (int) floor($totalSeconds / 86400);
            $hours = (int) floor(($totalSeconds % 86400) / 3600);
            $minutes = (int) floor(($totalSeconds % 3600) / 60);

            $parts = [];
            if ($days > 0) $parts[] = "{$days}d";
            if ($hours > 0) $parts[] = "{$hours}h";
            $parts[] = "{$minutes}m";
            return implode(' ', $parts) ?: '0m';
        }

        return $clean ?: 'Connected';
    }
}

