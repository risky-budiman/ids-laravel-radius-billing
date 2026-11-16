<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Status OLT & ONU belum terdaftar untuk dasbor NOC (web & API), di-cache 5 menit.
 *
 * Dulu setiap kali dasbor dibuka sistem memindai ONU belum terdaftar lewat SNMP ke semua OLT tanpa cache —
 * termasuk OLT yang mati, sehingga halaman menunggu timeout satu per satu.
 */
class NocSnapshot
{
    public const TTL = 300;

    /** @param Collection<int, Olt> $olts @return array<int, array> status per OLT id */
    public static function oltStatuses(Collection $olts, bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('noc_olt_stats');
        }

        return Cache::remember('noc_olt_stats', self::TTL, function () use ($olts) {
            $data = [];
            foreach ($olts as $olt) {
                try {
                    $data[$olt->id] = (new OltGateway($olt))->getOltStatus();
                } catch (\Throwable) {
                    $data[$olt->id] = ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
                }
            }

            return $data;
        });
    }

    /**
     * ONU belum terdaftar. OLT yang offline dilewati (tidak ditunggu sampai timeout); OLT yang gagal dipindai
     * dicatat di `failed` agar tidak dibaca sebagai "0 ONU".
     *
     * @param Collection<int, Olt> $olts
     * @return array{count: int, onus: list<array>, failed: list<string>, skipped: list<string>}
     */
    public static function unconfigured(Collection $olts, bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('noc_unconfigured');
        }
        $statuses = self::oltStatuses($olts, $fresh);

        return Cache::remember('noc_unconfigured', self::TTL, function () use ($olts, $statuses) {
            $onus = [];
            $failed = [];
            $skipped = [];
            foreach ($olts as $olt) {
                if (($statuses[$olt->id]['status'] ?? 'offline') !== 'online') {
                    $skipped[] = $olt->name;
                    continue;
                }
                try {
                    foreach ((new OltGateway($olt))->scanUnconfiguredOnus() as $onu) {
                        $onus[] = $onu + ['olt_id' => $olt->id, 'olt_name' => $olt->name];
                    }
                } catch (\Throwable) {
                    $failed[] = $olt->name;
                }
            }

            return ['count' => count($onus), 'onus' => $onus, 'failed' => $failed, 'skipped' => $skipped];
        });
    }
}
