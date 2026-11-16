<?php

namespace App\Services\Network;

use App\Models\Olt;
use App\Models\OltWorkItem;
use Illuminate\Support\Facades\Cache;

/**
 * Tutup otomatis pekerjaan manual di OLT dengan mencocokkan SN/MAC ke daftar ONU yang terbaca lewat SNMP:
 * - "daftarkan / allow ONU" selesai bila ONU dengan SN itu sudah muncul di OLT;
 * - "hapus ONU" selesai bila ONU itu sudah tidak ada di OLT (hanya bila SEMUA port terbaca, agar port yang
 *   gagal dipindai tidak dianggap kosong).
 * "Ganti profil T-CONT" tidak bisa dicek lewat SNMP sehingga tetap ditandai manual.
 */
class OltWorkVerifier
{
    /**
     * @param  bool  $cacheOnly  true = pakai hasil pindai yang tersimpan saja (tanpa SNMP), untuk saat halaman dibuka
     * @return array{done: int, ports_read: int, ports_total: int}
     */
    public function verify(Olt $olt, bool $cacheOnly = false): array
    {
        $pending = OltWorkItem::pending()->where('olt_id', $olt->id)->whereIn('type', ['provision', 'delete'])->whereNotNull('onu_sn')->get();
        $ports = $olt->ponPorts()->get();
        if ($pending->isEmpty() || $ports->isEmpty()) {
            return ['done' => 0, 'ports_read' => 0, 'ports_total' => $ports->count()];
        }

        $present = [];
        $read = 0;
        $gateway = null;
        foreach ($ports as $port) {
            $key = "olt_port_data_{$port->id}";
            $rows = Cache::get($key);
            if ($rows === null && ! $cacheOnly) {
                try {
                    $gateway ??= app()->makeWith(OltGateway::class, ['olt' => $olt]);
                    $rows = $gateway->getOnusOnPort($port->shelf ?: 1, $port->slot, $port->pon_port);
                    Cache::put($key, $rows, now()->addMinutes(10));
                } catch (\Throwable) {
                    $rows = null;
                }
            }
            if ($rows === null) {
                continue;
            }
            $read++;
            foreach ($rows as $row) {
                $present[OltWorkItem::normalizeSn($row['sn'] ?? '')] = $row;
            }
        }

        $allRead = $read === $ports->count();
        $done = 0;
        foreach ($pending as $item) {
            $sn = OltWorkItem::normalizeSn($item->onu_sn);
            $found = isset($present[$sn]);
            if (($item->type === 'provision' && $found) || ($item->type === 'delete' && ! $found && $allRead)) {
                $item->update(['status' => 'done', 'resolution' => 'auto', 'resolved_at' => now(),
                    'note' => $item->type === 'provision' ? 'Terlihat di OLT (dicek lewat SNMP).' : 'Sudah tidak ada di OLT (dicek lewat SNMP).']);
                $done++;
            }
        }

        return ['done' => $done, 'ports_read' => $read, 'ports_total' => $ports->count()];
    }
}
