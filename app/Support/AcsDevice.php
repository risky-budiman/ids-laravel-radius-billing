<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Ringkasan satu perangkat GenieACS (TR-098 InternetGatewayDevice / TR-181 Device / VirtualParameters):
 * nilai diambil dari jalur standar dulu, lalu jalur vendor & virtual parameter. Dipakai daftar & detail modem.
 */
class AcsDevice
{
    /** Perangkat dianggap online bila inform terakhir dalam rentang ini (menit). */
    public const ONLINE_MINUTES = 15;

    public static function summarize(array $d): array
    {
        $id = (string) ($d['_id'] ?? '');
        $v = fn (array $paths) => self::first($d, $paths);

        $serial = $v(['_deviceId._SerialNumber', 'DeviceID.SerialNumber', 'Device.DeviceInfo.SerialNumber', 'InternetGatewayDevice.DeviceInfo.SerialNumber']);
        if (! $serial && $id !== '') {
            $parts = explode('-', $id);
            $serial = end($parts);
        }

        $rawTags = $d['_tags'] ?? $d['Tags'] ?? [];
        $tags = array_values(array_filter(array_map('trim', is_array($rawTags) ? $rawTags : explode(',', (string) $rawTags)), 'strlen'));

        $lastInform = isset($d['_lastInform']) ? Carbon::parse($d['_lastInform']) : null;

        return [
            'id' => $id,
            'serial' => (string) $serial,
            'model' => (string) ($v(['_deviceId._ProductClass', 'DeviceID.ProductClass', 'Device.DeviceInfo.ProductClass', 'InternetGatewayDevice.DeviceInfo.ProductClass',
                'Device.DeviceInfo.ModelName', 'InternetGatewayDevice.DeviceInfo.ModelName', 'VirtualParameters.ProductClass']) ?? ''),
            'manufacturer' => self::brand((string) ($v(['_deviceId._Manufacturer', 'DeviceID.Manufacturer', 'Device.DeviceInfo.Manufacturer',
                'InternetGatewayDevice.DeviceInfo.Manufacturer']) ?? ''), $id),
            'ip' => $v(['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                'Device.IP.Interface.1.IPv4Address.1.IPAddress', 'Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                'Device.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress', 'VirtualParameters.wanip', 'VirtualParameters.IP']),
            'pppoe' => $v(['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username',
                'Device.PPP.Interface.1.Username', 'Device.PPP.Interface.2.Username', 'VirtualParameters.pppUsername']),
            'rx' => self::rx($v(['InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RXPower', 'InternetGatewayDevice.WANDevice.1.X_HW_OpticalInfo.RxPower',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_ZTE-COM_WANPONInterfaceConfig.RXPower',
                'InternetGatewayDevice.WANDevice.1.X_ZTE-COM_Optical.RxPower', 'InternetGatewayDevice.X_ALU_OntOpticalParam.RXPower',
                'Device.Optical.Interface.1.OpticalSignalLevel', 'Device.Optical.Interface.1.OpticalPowerRx', 'VirtualParameters.getponrx'])),
            'tags' => $tags,
            'last_inform' => $lastInform,
            'online' => $lastInform !== null && $lastInform->gt(now()->subMinutes(self::ONLINE_MINUTES)),
        ];
    }

    /**
     * Tautkan ringkasan perangkat ke pelanggan lewat username PPPoE (sama persis, atau tanpa @domain).
     *
     * @param  \Illuminate\Support\Collection<int, array>  $rows  hasil summarize()
     */
    public static function attachCustomers(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        $usernames = $rows->pluck('pppoe')->filter()->map(fn ($u) => strtolower((string) $u))->unique()->values();
        $customers = collect();
        if ($usernames->isNotEmpty()) {
            $bases = $usernames->map(fn ($u) => explode('@', $u)[0])->unique()->values();
            $customers = \App\Models\Customer::query()
                ->where(function ($q) use ($usernames, $bases) {
                    $q->whereIn('username', $usernames->all());
                    foreach ($bases as $b) {
                        $q->orWhere('username', $b)->orWhere('username', 'like', addcslashes($b, '%_\\') . '@%');
                    }
                })
                ->get(['id', 'customer_code', 'name', 'username', 'status']);
        }

        return $rows->map(function ($r) use ($customers) {
            $u = strtolower((string) $r['pppoe']);
            $base = explode('@', $u)[0];
            $r['customer'] = $u === '' ? null : ($customers->first(fn ($c) => strtolower($c->username) === $u)
                ?? $customers->first(fn ($c) => explode('@', strtolower($c->username))[0] === $base));

            return $r;
        });
    }

    /** Nilai pertama yang ada dari daftar jalur bertitik (mengambil `_value` bila berupa parameter). */
    public static function first(array $d, array $paths): mixed
    {
        foreach ($paths as $path) {
            $node = $d;
            foreach (explode('.', $path) as $key) {
                if (! is_array($node) || ! array_key_exists($key, $node)) {
                    continue 2;
                }
                $node = $node[$key];
            }
            $val = is_array($node) ? ($node['_value'] ?? null) : $node;
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        return null;
    }

    /** Daya Rx dalam dBm (beberapa vendor mengirim satuan 0,01 atau 0,1 dBm). */
    public static function rx(mixed $raw): ?float
    {
        if ($raw === null || ! is_numeric($raw)) {
            return null;
        }
        $rx = (float) $raw;
        if ($rx < -1000) {
            $rx /= 100;
        } elseif ($rx < -100) {
            $rx /= 10;
        }

        return ($rx === 0.0 || $rx < -60 || $rx > 10) ? null : round($rx, 2);
    }

    /** Nama merek singkat dari Manufacturer, atau dari OUI pada ID perangkat bila kosong. */
    public static function brand(string $manufacturer, string $id = ''): string
    {
        $m = strtolower($manufacturer . ' ' . $id);
        foreach (['huawei' => 'Huawei', 'hwtc' => 'Huawei', 'zte' => 'ZTE', 'fiberhome' => 'FiberHome', 'nokia' => 'Nokia', 'alcatel' => 'Nokia',
            'tp-link' => 'TP-Link', 'tplink' => 'TP-Link', 'xpon' => 'XPON', 'cdata' => 'C-Data', 'fd51' => 'C-Data', 'zxhn' => 'ZTE', 'hg8' => 'Huawei'] as $needle => $label) {
            if (str_contains($m, $needle)) {
                return $label;
            }
        }

        return trim($manufacturer);
    }

    /** Kelas warna sinyal (sama dengan halaman OLT): ≥ -24 baik, -24 s/d -27 waspada, < -27 kritis. */
    public static function signalLevel(?float $rx): string
    {
        return $rx === null ? 'none' : ($rx >= -24 ? 'good' : ($rx >= -27 ? 'warn' : 'bad'));
    }
}
