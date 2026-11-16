<?php

namespace App\Services\Network;

use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\CustomerSignalLog;
use App\Models\Olt;
use App\Models\OltWorkItem;
use App\Models\Radius\RadAcct;
use Illuminate\Support\Facades\Cache;

/**
 * Logika NOC dalam satu tempat (dasbor web, API staf, noc:monitor, tombol sinkron).
 *
 * Dulu polling memakai tabel SNMP khusus ZTE sehingga untuk OLT HSGQ semua ONU terbaca kosong: sinyal,
 * status, peringatan LOS/kritis & riwayat sinyal tidak pernah terisi. Sekarang ONU dibaca per port PON lewat
 * OltGateway::getOnusOnPort (HSGQ & ZTE — jalur yang sama dengan halaman port OLT) lalu dicocokkan ke
 * pelanggan lewat SN/MAC yang dinormalkan (huruf & pemisah tidak berpengaruh).
 */
class NocService
{
    public const CRITICAL_DBM = -27.0;
    public const WARN_DBM = -24.0;
    public const DOWN_STATUSES = ['los', 'dying-gasp', 'offline'];

    /** Batas waktu pemindaian dari tombol di halaman (detik). */
    public const SYNC_TIME_LIMIT = 180;

    public function gateway(Olt $olt): OltGateway
    {
        return app()->makeWith(OltGateway::class, ['olt' => $olt]);
    }

    /**
     * Baca semua ONU satu OLT per port PON. Hasil per port juga disimpan ke cache halaman port (10 menit).
     *
     * @return array{onus: list<array>, ports_read: int, ports_total: int, failed_ports: list<string>, reachable: bool}
     */
    public function scanOlt(Olt $olt): array
    {
        $gateway = $this->gateway($olt);
        $ports = $olt->ponPorts()->orderBy('slot')->orderBy('pon_port')->get();
        $onus = [];
        $failed = [];
        $read = 0;
        foreach ($ports as $port) {
            try {
                $rows = $gateway->getOnusOnPort($port->shelf ?: 1, $port->slot, $port->pon_port);
                Cache::put("olt_port_data_{$port->id}", $rows, now()->addMinutes(10));
                $read++;
                foreach ($rows as $row) {
                    $onus[] = $row + ['port_id' => $port->id, 'port_label' => "{$port->slot}/{$port->pon_port}"];
                }
            } catch (\Throwable) {
                $failed[] = "{$port->slot}/{$port->pon_port}";
            }
        }

        return ['onus' => $onus, 'ports_read' => $read, 'ports_total' => $ports->count(), 'failed_ports' => $failed,
            'reachable' => $read > 0 || $ports->isEmpty()];
    }

    public static function norm(?string $sn): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $sn));
    }

    /** Kunci login pelanggan: username tanpa @domain (huruf kecil). */
    public static function loginKey(?string $v): string
    {
        return strtolower(trim(explode('@', (string) $v)[0]));
    }

    /** MAC/SN untuk disimpan di data pelanggan: MAC 12 heksa → "E8:68:19:02:FF:70", lainnya huruf besar. */
    public static function storableSn(string $sn): string
    {
        $hex = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $sn));

        return strlen($hex) === 12 && ! preg_match('/[^0-9A-F]/', $hex) && str_contains($sn, ':') ? implode(':', str_split($hex, 2)) : strtoupper(trim($sn));
    }

    /**
     * Cocokkan baris ONU ke pelanggan: (1) SN/MAC yang dinormalkan, lalu (2) NAMA ONU = username pelanggan
     * tanpa @domain atau kode pelanggan (OLT HSGQ menamai ONU dengan ID PPPoE). Nama yang dipakai lebih dari
     * satu pelanggan diabaikan agar tidak salah tautkan. Kandidat: pelanggan OLT ini + yang OLT-nya belum diisi.
     *
     * @return array<string, array{customer: Customer, via: string}>  [index ONU => kecocokan]
     */
    public function matchRows(Olt $olt, array $rows): array
    {
        $candidates = Customer::where(fn ($q) => $q->where('olt_id', $olt->id)->orWhereNull('olt_id'))
            ->whereNotIn('status', [Customer::STATUS_CANCELED])->get();
        $bySn = [];
        $byLogin = [];
        foreach ($candidates as $c) {
            if ($k = self::norm($c->onu_sn)) {
                $bySn[$k] = $c;
            }
            foreach (array_unique(array_filter([self::loginKey($c->username), strtolower(trim((string) $c->customer_code))])) as $k) {
                $byLogin[$k] = isset($byLogin[$k]) && $byLogin[$k]->id !== $c->id ? false : $c;   // false = ambigu
            }
        }

        $out = [];
        $used = [];
        foreach ($rows as $row) {
            $c = $bySn[self::norm($row['sn'] ?? null)] ?? null;
            $via = 'sn';
            if (! $c) {
                $name = strtolower(trim((string) ($row['name'] ?? '')));
                $c = $name !== '' ? ($byLogin[$name] ?? null) : null;
                $via = 'name';
            }
            if ($c && ! isset($used[$c->id]) && isset($row['index'])) {
                $used[$c->id] = true;
                $out[(string) $row['index']] = ['customer' => $c, 'via' => $via];
            }
        }

        return $out;
    }

    /**
     * Tautkan pelanggan ke ONU: OLT, posisi, dan MAC/SN (bila kosong, atau bila $replaceSn). Dipakai penautan
     * otomatis (nama ONU) & manual.
     */
    public function linkCustomer(Customer $customer, Olt $olt, array $row, bool $replaceSn = false): void
    {
        $data = ['olt_id' => $olt->id];
        if (! empty($row['index'])) {
            $data['onu_index'] = (string) $row['index'];
        }
        if (! empty($row['sn']) && ($replaceSn || ! filled($customer->onu_sn))) {
            $data['onu_sn'] = self::storableSn((string) $row['sn']);
        }
        $customer->forceFill($data)->saveQuietly();
    }

    /**
     * Cocokkan hasil pindai ke pelanggan, tautkan yang baru cocok, perbarui cache sinyal & riwayat. Pelanggan
     * OLT ini yang ONU-nya tidak ditemukan (padahal semua port terbaca) ditandai `not_found`.
     *
     * @return array{matched: int, linked: int, not_found: int, los: list<string>, critical: list<string>}
     */
    public function syncCustomers(Olt $olt, array $scan, bool $collectAlerts = false, int $realertHours = 6): array
    {
        $allRead = $scan['ports_read'] === $scan['ports_total'];
        $result = ['matched' => 0, 'linked' => 0, 'not_found' => 0, 'los' => [], 'critical' => []];
        $matches = $this->matchRows($olt, $scan['onus']);
        $rowsByIndex = collect($scan['onus'])->keyBy(fn ($r) => (string) ($r['index'] ?? ''));
        $matchedIds = collect($matches)->map(fn ($m) => $m['customer']->id)->all();
        $previous = CustomerSignalCache::whereIn('customer_id', $matchedIds)->get()->keyBy('customer_id');

        // Pelanggan OLT ini ber-SN yang tidak ditemukan sama sekali
        if ($allRead) {
            $missing = Customer::where('olt_id', $olt->id)->whereNotNull('onu_sn')->where('onu_sn', '!=', '')
                ->whereNotIn('id', $matchedIds)->get(['id', 'onu_index']);
            foreach ($missing as $m) {
                $old = CustomerSignalCache::where('customer_id', $m->id)->value('onu_index');
                CustomerSignalCache::updateOrCreate(['customer_id' => $m->id],
                    ['status' => 'not_found', 'rx_power' => null, 'last_polled_at' => now(), 'onu_index' => $old ?? (string) $m->onu_index]);
                $result['not_found']++;
            }
        }

        foreach ($matches as $index => $match) {
            $customer = $match['customer'];
            $row = $rowsByIndex[$index];
            $prev = $previous[$customer->id] ?? null;
            $result['matched']++;
            if (! $customer->olt_id || ! filled($customer->onu_sn)) {
                $this->linkCustomer($customer, $olt, $row);
                $result['linked']++;
            }
            $dbm = is_numeric($row['signal'] ?? null) ? (float) $row['signal'] : null;
            $status = strtolower((string) ($row['status'] ?? 'unknown'));

            $alerted = false;
            $canAlert = ! $prev?->last_alerted_at || $prev->last_alerted_at->lt(now()->subHours($realertHours));
            // Hanya pelanggan aktif: ONU pelanggan terisolir/dicabut yang mati bukan gangguan
            if ($collectAlerts && $customer->status === Customer::STATUS_ACTIVE && $canAlert) {
                if (in_array($status, self::DOWN_STATUSES, true) && $prev?->status === 'online') {
                    $result['los'][] = "{$customer->customer_code} {$customer->name} ({$status})";
                    $alerted = true;
                } elseif ($dbm !== null && $dbm < self::CRITICAL_DBM) {
                    $result['critical'][] = "{$customer->customer_code} {$customer->name}: {$dbm} dBm";
                    $alerted = true;
                }
            }

            CustomerSignalCache::updateOrCreate(['customer_id' => $customer->id], array_filter([
                'onu_index' => (string) ($row['index'] ?? $customer->onu_index ?? ''),
                'rx_power' => $dbm,
                'status' => $status,
                'last_polled_at' => now(),
                'last_alerted_at' => $alerted ? now() : null,
            ], fn ($v, $k) => $k !== 'last_alerted_at' || $v !== null, ARRAY_FILTER_USE_BOTH));

            // Posisi ONU berpindah (ONU yang sama di port/ID lain): perbarui data pelanggan
            if ($status === 'online' && ! empty($row['index']) && $this->positionOf($row['index']) !== $this->positionOf($customer->onu_index)) {
                $customer->forceFill(['onu_index' => $row['index']])->saveQuietly();
            }
            $this->logHistory($customer->id, $dbm, $status);
        }

        return $result;
    }

    private function positionOf(?string $index): string
    {
        return implode('.', array_slice(array_values(array_filter(preg_split('/[.\/:]/', (string) $index), 'strlen')), 0, 4));
    }

    private function logHistory(int $customerId, ?float $dbm, string $status): void
    {
        $last = CustomerSignalLog::where('customer_id', $customerId)->latest('created_at')->first();
        $should = ! $last || $last->status !== $status
            || ($dbm !== null && ($last->rx_power === null || abs($last->rx_power - $dbm) >= 0.5))
            || $last->created_at->lt(now()->subMinutes(10));
        if ($should && ($dbm !== null || $status !== ($last?->status))) {
            CustomerSignalLog::create(['customer_id' => $customerId, 'rx_power' => $dbm, 'status' => $status, 'created_at' => now()]);
        }
    }

    /**
     * Pindai semua OLT aktif & perbarui sinyal pelanggan. Dipakai tombol "Sinkron sekarang" (tanpa antrean).
     *
     * @return array{olts: list<array>, matched: int, not_found: int, at: int}
     */
    public function syncAll(): array
    {
        @set_time_limit(self::SYNC_TIME_LIMIT);
        $out = ['olts' => [], 'matched' => 0, 'not_found' => 0];
        foreach (Olt::where('is_active', true)->get() as $olt) {
            $scan = $this->scanOlt($olt);
            $r = $scan['reachable'] ? $this->syncCustomers($olt, $scan) : ['matched' => 0, 'not_found' => 0];
            $out['olts'][] = ['id' => $olt->id, 'name' => $olt->name, 'reachable' => $scan['reachable'], 'onus' => count($scan['onus']),
                'failed_ports' => $scan['failed_ports'], 'matched' => $r['matched']];
            $out['matched'] += $r['matched'];
            $out['not_found'] += $r['not_found'];
        }
        $out['at'] = now()->getTimestamp();
        Cache::put('noc_last_sync_at', $out['at'], now()->addDays(7));

        return $out;
    }

    /**
     * ONU yang terlihat OLT tapi belum terdaftar/diizinkan. HSGQ: lewat API web OLT (menunggu izin); ZTE: tabel
     * SNMP ONU belum terkonfigurasi. OLT yang tidak bisa dibaca dilaporkan, bukan dianggap "0 ONU".
     *
     * @return array{onus: list<array>, olts: list<array>, at: int}
     */
    public function unregistered(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('noc_unregistered');
        }

        return Cache::remember('noc_unregistered', NocSnapshot::TTL, function () {
            @set_time_limit(self::SYNC_TIME_LIMIT);
            $onus = [];
            $olts = [];
            $linked = Customer::whereNotNull('onu_sn')->get(['id', 'customer_code', 'name', 'onu_sn', 'status'])
                ->keyBy(fn ($c) => self::norm($c->onu_sn));
            $work = OltWorkItem::pending()->where('type', 'provision')->get()->keyBy(fn ($w) => self::norm($w->onu_sn));

            foreach (Olt::where('is_active', true)->with('ponPorts')->get() as $olt) {
                $info = ['id' => $olt->id, 'name' => $olt->name, 'state' => 'ok', 'message' => null, 'count' => 0];
                try {
                    $rows = $this->unregisteredOn($olt);
                    if ($rows === null) {
                        $info['state'] = 'unsupported';
                        $info['message'] = str_contains(strtoupper((string) $olt->olt_type), 'HSGQ')
                            ? 'Isi akses web OLT (Ubah OLT) untuk melihat ONU yang menunggu izin.'
                            : 'Tipe OLT ini belum didukung untuk pencarian ONU belum terdaftar.';
                        $rows = [];
                    }
                } catch (\Throwable $e) {
                    $info['state'] = 'failed';
                    $info['message'] = 'Tidak terbaca: ' . \Illuminate\Support\Str::limit($e->getMessage(), 120);
                    $rows = [];
                }
                foreach ($rows as $row) {
                    $key = self::norm($row['sn']);
                    $c = $linked[$key] ?? null;
                    $w = $work[$key] ?? null;
                    $onus[] = $row + [
                        'olt_id' => $olt->id, 'olt_name' => $olt->name,
                        'customer' => $c ? ['id' => $c->id, 'code' => $c->customer_code, 'name' => $c->name] : null,
                        'work_item' => $w?->id,
                    ];
                }
                $info['count'] = count($rows);
                $olts[] = $info;
            }

            return ['onus' => $onus, 'olts' => $olts, 'at' => now()->getTimestamp()];
        });
    }

    /** @return list<array{sn: string, port_id: ?int, port: string, source: string, note: string}>|null  null = tidak didukung */
    private function unregisteredOn(Olt $olt): ?array
    {
        if (str_contains(strtoupper((string) $olt->olt_type), 'HSGQ')) {
            if (! filled($olt->web_username) || ! filled($olt->web_password)) {
                return null;
            }
            $web = new HsgqWebClient($olt, 10);
            $rows = [];
            foreach ($olt->ponPorts as $port) {
                foreach ($web->pendingOnus((int) $port->pon_port) as $p) {
                    $rows[] = ['sn' => $p['mac'], 'port_id' => $port->id, 'port' => "{$port->slot}/{$port->pon_port}",
                        'source' => $p['source'], 'note' => $p['source'] === 'denied' ? 'Di daftar tolak' : 'Belum diizinkan'];
                }
            }

            return $rows;
        }

        $rows = [];
        foreach ($this->gateway($olt)->scanUnconfiguredOnus() as $onu) {
            $rows[] = ['sn' => (string) ($onu['sn'] ?? ''), 'port_id' => null,
                'port' => trim(($onu['slot'] ?? '') . '/' . ($onu['port'] ?? ''), '/'), 'source' => 'unconfigured', 'note' => 'Belum terdaftar'];
        }

        return $rows;
    }

    /** Angka dasbor NOC (tanpa SNMP; dari RADIUS, data pelanggan & cache sinyal). */
    public function dashboard(): array
    {
        $active = Customer::where('status', Customer::STATUS_ACTIVE);
        $activeUsernames = (clone $active)->pluck('username')->filter()->map(fn ($u) => strtolower($u))->all();
        $online = RadAcct::online()->distinct()->pluck('username')->map(fn ($u) => strtolower((string) $u))->all();
        $onlineSet = array_flip($online);
        $activeOnline = count(array_filter($activeUsernames, fn ($u) => isset($onlineSet[$u])));
        $suspendedOnline = Customer::where('status', Customer::STATUS_SUSPENDED)->pluck('username')
            ->filter(fn ($u) => isset($onlineSet[strtolower((string) $u)]))->count();

        $signals = CustomerSignalCache::query()
            ->join('customers', 'customers.id', '=', 'customer_signal_caches.customer_id')
            ->whereIn('customers.status', Customer::BILLABLE_STATUSES);
        $count = fn ($q) => (clone $signals)->where($q)->count();

        return [
            'customers_active' => count($activeUsernames),
            'pppoe_online' => $activeOnline,
            'pppoe_offline' => max(0, count($activeUsernames) - $activeOnline),
            'isolated_online' => $suspendedOnline,
            'onu_tracked' => (clone $signals)->count(),
            'onu_online' => $count(fn ($q) => $q->where('customer_signal_caches.status', 'online')),
            'onu_down' => $count(fn ($q) => $q->whereIn('customer_signal_caches.status', self::DOWN_STATUSES)),
            'onu_not_found' => $count(fn ($q) => $q->where('customer_signal_caches.status', 'not_found')),
            'signal_good' => $count(fn ($q) => $q->where('rx_power', '>=', self::WARN_DBM)),
            'signal_warn' => $count(fn ($q) => $q->where('rx_power', '<', self::WARN_DBM)->where('rx_power', '>=', self::CRITICAL_DBM)),
            'signal_critical' => $count(fn ($q) => $q->where('rx_power', '<', self::CRITICAL_DBM)),
            'last_sync_at' => Cache::get('noc_last_sync_at') ?: optional(CustomerSignalCache::max('last_polled_at'), fn ($v) => strtotime($v)),
        ];
    }
}
