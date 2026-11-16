<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\Olt;
use App\Services\Network\NocService;
use App\Services\Network\NocSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * NOC Center: ringkasan jaringan, sinyal optik pelanggan, ONU belum terdaftar & riwayat sinyal.
 * Semua angka dari NocService; pemindaian dijalankan langsung dari halaman (dulu lewat antrean yang tidak
 * pernah diproses tanpa worker, sehingga "sedang memindai" tertahan selamanya).
 */
class NocController extends Controller
{
    public function __construct(private NocService $noc)
    {
    }

    public function index(Request $request)
    {
        $olts = Olt::where('is_active', true)->withCount('ponPorts')->orderBy('name')->get();
        $oltStats = NocSnapshot::oltStatuses($olts, $request->boolean('refresh'));
        $stats = $this->noc->dashboard();

        // ONU per OLT dari hasil pindai terakhir (cache port, tanpa SNMP)
        $oltOnus = [];
        foreach ($olts as $olt) {
            $total = $online = 0;
            $read = false;
            foreach ($olt->ponPorts()->pluck('id') as $portId) {
                $rows = Cache::get("olt_port_data_{$portId}");
                if (is_array($rows)) {
                    $read = true;
                    $total += count($rows);
                    $online += collect($rows)->where('status', 'online')->count();
                }
            }
            $oltOnus[$olt->id] = $read ? ['total' => $total, 'online' => $online] : null;
        }

        $base = fn () => CustomerSignalCache::query()->with('customer:id,customer_code,name,status,olt_id,onu_sn')
            ->whereHas('customer', fn ($q) => $q->where('status', Customer::STATUS_ACTIVE));
        $down = $base()->whereIn('status', [...NocService::DOWN_STATUSES, 'not_found'])->orderByDesc('last_polled_at')->limit(8)->get();
        $critical = $base()->where('rx_power', '<', NocService::CRITICAL_DBM)->orderBy('rx_power')->limit(8)->get();

        $unregistered = Cache::get('noc_unregistered');
        $openFaults = \App\Models\Ticket::where('type', 'gangguan')->whereNotIn('status', ['resolved', 'closed'])->count();

        return view('noc.index', compact('stats', 'olts', 'oltStats', 'oltOnus', 'down', 'critical', 'unregistered', 'openFaults'));
    }

    /** Tombol "Sinkron sekarang": pindai semua OLT & perbarui sinyal pelanggan (langsung, tanpa antrean). */
    public function sync()
    {
        try {
            $r = $this->noc->syncAll();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sinkron gagal: ' . $e->getMessage()], 500);
        }
        $failed = collect($r['olts'])->where('reachable', false)->pluck('name');
        $onus = collect($r['olts'])->sum('onus');
        $msg = "{$onus} ONU terbaca, {$r['matched']} pelanggan diperbarui."
            . ($r['not_found'] ? " {$r['not_found']} ONU pelanggan tidak ditemukan di OLT." : '')
            . ($failed->isNotEmpty() ? ' OLT tidak merespons: ' . $failed->implode(', ') . '.' : '');

        return response()->json(['success' => true, 'message' => $msg, 'result' => $r]);
    }

    public function discovery(Request $request)
    {
        $data = Cache::get('noc_unregistered');

        return view('noc.discovery', ['data' => $data]);
    }

    /** Pindai ONU belum terdaftar di semua OLT (langsung, hasil disimpan 5 menit). */
    public function scanDiscovery()
    {
        try {
            $data = $this->noc->unregistered(true);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Pemindaian gagal: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'count' => count($data['onus']), 'data' => $data]);
    }

    /**
     * Penautan ONU ↔ pelanggan per OLT, dari hasil pindai terakhir (cache port). Usulan: nama ONU = username/
     * kode pelanggan, atau deskripsi ONU (web OLT HSGQ) = nama pelanggan — keduanya harus unik.
     */
    public function linking(Request $request)
    {
        $olts = Olt::where('is_active', true)->with('ponPorts')->orderBy('name')->get();
        $oltId = $request->integer('olt') ?: $olts->first()?->id;
        $olt = $olts->firstWhere('id', $oltId);
        $tab = in_array($request->get('tab'), ['suggested', 'unlinked', 'linked'], true) ? $request->get('tab') : 'suggested';
        $data = $olt ? $this->linkingData($olt) : ['rows' => [], 'scanned' => false];
        $counts = collect($data['rows'])->countBy('state')->all() + ['linked' => 0, 'suggested' => 0, 'unlinked' => 0];
        $rows = collect($data['rows'])->where('state', $tab)->values();

        return view('noc.linking', compact('olts', 'olt', 'tab', 'rows', 'counts') + ['scanned' => $data['scanned']]);
    }

    /** @return array{rows: list<array>, scanned: bool} */
    private function linkingData(Olt $olt): array
    {
        $rows = [];
        $desc = [];
        $scanned = false;
        $web = null;   // satu login web OLT untuk semua port (salah login berulang bisa mengunci akun)
        $hasWeb = str_contains(strtoupper((string) $olt->olt_type), 'HSGQ') && filled($olt->web_username) && filled($olt->web_password);
        foreach ($olt->ponPorts as $port) {
            $portRows = Cache::get("olt_port_data_{$port->id}");
            if (! is_array($portRows)) {
                continue;
            }
            $scanned = true;
            $portDesc = Cache::get("olt_port_desc_{$port->id}");
            if ($portDesc === null && $hasWeb) {
                try {
                    $web ??= new \App\Services\Network\HsgqWebClient($olt, 10);
                    $portDesc = $web->onuDetails((int) $port->pon_port);
                    Cache::put("olt_port_desc_{$port->id}", $portDesc, now()->addMinutes(10));
                } catch (\Throwable) {
                    $hasWeb = false;   // jangan coba port lain bila login/akses gagal
                    $portDesc = [];
                }
            }
            $desc += (array) $portDesc;
            foreach ($portRows as $r) {
                $rows[] = $r + ['port_id' => $port->id];
            }
        }
        $matches = $this->noc->matchRows($olt, $rows);

        // Usulan dari deskripsi ONU = nama pelanggan (unik, belum dipakai ONU lain)
        $usedIds = collect($matches)->map(fn ($m) => $m['customer']->id)->all();
        $byName = Customer::where(fn ($q) => $q->where('olt_id', $olt->id)->orWhereNull('olt_id'))
            ->whereNotIn('status', [Customer::STATUS_CANCELED])->whereNotIn('id', $usedIds)->get()
            ->groupBy(fn ($c) => mb_strtolower(trim($c->name)));

        $out = [];
        foreach ($rows as $r) {
            $key = NocService::norm($r['sn'] ?? null);
            $description = trim((string) ($desc[$key]['description'] ?? ''));
            $m = $matches[(string) $r['index']] ?? null;
            $c = $m['customer'] ?? null;
            $reason = null;
            if ($c && $m['via'] === 'sn' && (int) $c->olt_id === (int) $olt->id) {
                $state = 'linked';
            } elseif ($c) {
                $state = 'suggested';
                $reason = $m['via'] === 'sn' ? 'MAC/SN sama' : 'nama ONU = username/kode pelanggan';
            } elseif ($description !== '' && ($cands = $byName[mb_strtolower($description)] ?? collect())->count() === 1) {
                $c = $cands->first();
                $state = 'suggested';
                $reason = 'deskripsi ONU = nama pelanggan';
            } else {
                $state = 'unlinked';
            }
            $out[] = ['index' => (string) $r['index'], 'port_id' => $r['port_id'], 'name' => (string) ($r['name'] ?? ''), 'sn' => (string) ($r['sn'] ?? ''),
                'description' => $description, 'status' => $r['status'] ?? null, 'signal' => $r['signal'] ?? null,
                'state' => $state, 'reason' => $reason, 'customer' => $c];
        }
        usort($out, fn ($a, $b) => strnatcmp($a['index'], $b['index']));

        return ['rows' => $out, 'scanned' => $scanned];
    }

    /** Baris ONU dari cache port OLT menurut posisi. */
    private function cachedRow(Olt $olt, string $index): ?array
    {
        foreach ($olt->ponPorts()->pluck('id') as $portId) {
            foreach ((array) Cache::get("olt_port_data_{$portId}", []) as $r) {
                if ((string) ($r['index'] ?? '') === $index) {
                    return $r;
                }
            }
        }

        return null;
    }

    /** Tautkan satu pelanggan ke ONU (menyimpan OLT, posisi & MAC/SN ONU di data pelanggan). */
    public function link(Request $request)
    {
        $data = $request->validate(['olt_id' => 'required|integer|exists:olts,id', 'index' => 'required|string|max:40',
            'customer_id' => 'required|integer|exists:customers,id']);
        $olt = Olt::findOrFail($data['olt_id']);
        $row = $this->cachedRow($olt, $data['index']);
        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Data ONU kedaluwarsa. Klik Sinkron ONU lalu coba lagi.'], 422);
        }
        $customer = Customer::findOrFail($data['customer_id']);
        $key = NocService::norm($row['sn'] ?? null);
        $owner = $key ? Customer::whereKeyNot($customer->id)->whereNotNull('onu_sn')->get(['id', 'name', 'customer_code', 'onu_sn'])
            ->first(fn ($c) => NocService::norm($c->onu_sn) === $key) : null;
        if ($owner) {
            return response()->json(['success' => false, 'message' => "ONU ini sudah tertaut ke {$owner->customer_code} {$owner->name}. Lepas tautannya dulu."], 422);
        }

        $before = $customer->only(['olt_id', 'onu_sn', 'onu_index']);
        $this->noc->linkCustomer($customer, $olt, $row, replaceSn: true);
        \App\Models\CustomerSignalCache::where('customer_id', $customer->id)->delete();   // terisi ulang saat sinkron berikutnya
        \App\Models\ActivityLog::log('onu_linked', "Pelanggan {$customer->customer_code} {$customer->name} ditautkan ke ONU {$row['sn']} ({$olt->name} {$data['index']}).",
            $customer, ['old' => $before, 'new' => $customer->fresh()->only(['olt_id', 'onu_sn', 'onu_index'])]);

        return response()->json(['success' => true, 'message' => "{$customer->name} ditautkan ke ONU " . strtoupper((string) $row['sn']) . '.']);
    }

    /** Tautkan semua usulan di satu OLT sekaligus. */
    public function linkSuggested(Request $request)
    {
        $olt = Olt::with('ponPorts')->findOrFail($request->validate(['olt_id' => 'required|integer|exists:olts,id'])['olt_id']);
        $n = 0;
        foreach ($this->linkingData($olt)['rows'] as $r) {
            if ($r['state'] !== 'suggested' || ! $r['customer']) {
                continue;
            }
            $this->noc->linkCustomer($r['customer'], $olt, ['index' => $r['index'], 'sn' => $r['sn']],
                replaceSn: ! filled($r['customer']->onu_sn) || $r['reason'] !== 'MAC/SN sama');
            $n++;
        }
        \App\Models\ActivityLog::log('onu_linked_bulk', "{$n} pelanggan ditautkan ke ONU di {$olt->name} (usulan otomatis).");

        return back()->with('success', "{$n} pelanggan ditautkan. Klik Sinkron ONU untuk mengisi sinyal & status mereka.");
    }

    /** Lepas tautan ONU dari pelanggan (MAC/SN & posisi dikosongkan). */
    public function unlink(Customer $customer)
    {
        $before = $customer->only(['olt_id', 'onu_sn', 'onu_index']);
        $customer->forceFill(['onu_sn' => null, 'onu_index' => null])->saveQuietly();
        \App\Models\CustomerSignalCache::where('customer_id', $customer->id)->delete();
        \App\Models\ActivityLog::log('onu_unlinked', "Tautan ONU pelanggan {$customer->customer_code} {$customer->name} dilepas.", $customer, ['old' => $before]);

        return request()->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Tautan ONU dilepas.'])
            : back()->with('success', 'Tautan ONU dilepas.');
    }

    public function signals(Request $request)
    {
        $filter = in_array($request->get('filter'), ['critical', 'warn', 'good', 'down', 'not_found', 'nodata'], true) ? $request->get('filter') : null;
        $oltId = $request->integer('olt') ?: null;
        $q = trim((string) $request->get('q'));

        $query = Customer::query()
            ->whereIn('customers.status', Customer::BILLABLE_STATUSES)
            ->whereNotNull('onu_sn')->where('onu_sn', '!=', '')
            ->leftJoin('customer_signal_caches as s', 's.customer_id', '=', 'customers.id')
            ->with('olt:id,name')
            ->select('customers.*', 's.rx_power', 's.status as onu_status', 's.last_polled_at', 's.onu_index as polled_index');

        $counts = [
            'all' => (clone $query)->count(),
            'critical' => (clone $query)->where('s.rx_power', '<', NocService::CRITICAL_DBM)->count(),
            'warn' => (clone $query)->where('s.rx_power', '<', NocService::WARN_DBM)->where('s.rx_power', '>=', NocService::CRITICAL_DBM)->count(),
            'good' => (clone $query)->where('s.rx_power', '>=', NocService::WARN_DBM)->count(),
            'down' => (clone $query)->whereIn('s.status', NocService::DOWN_STATUSES)->count(),
            'not_found' => (clone $query)->where('s.status', 'not_found')->count(),
            'nodata' => (clone $query)->whereNull('s.customer_id')->count(),
        ];

        match ($filter) {
            'critical' => $query->where('s.rx_power', '<', NocService::CRITICAL_DBM),
            'warn' => $query->where('s.rx_power', '<', NocService::WARN_DBM)->where('s.rx_power', '>=', NocService::CRITICAL_DBM),
            'good' => $query->where('s.rx_power', '>=', NocService::WARN_DBM),
            'down' => $query->whereIn('s.status', NocService::DOWN_STATUSES),
            'not_found' => $query->where('s.status', 'not_found'),
            'nodata' => $query->whereNull('s.customer_id'),
            default => null,
        };
        if ($oltId) {
            $query->where('customers.olt_id', $oltId);
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(fn ($w) => $w->where('customers.name', 'like', $like)->orWhere('customers.customer_code', 'like', $like)
                ->orWhere('customers.username', 'like', $like)->orWhere('customers.onu_sn', 'like', $like));
        }
        // Terburuk lebih dulu: tanpa data & sinyal paling lemah di atas
        $customers = $query->orderByRaw('s.rx_power IS NULL DESC')->orderBy('s.rx_power')->orderBy('customers.name')
            ->paginate(50)->withQueryString();

        $olts = Olt::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $lastSync = Cache::get('noc_last_sync_at');

        return view('noc.signals', compact('customers', 'counts', 'filter', 'oltId', 'q', 'olts', 'lastSync'));
    }

    public function history(Request $request, $id)
    {
        $customer = Customer::with(['olt:id,name', 'signalCache'])->findOrFail($id);
        $days = in_array((int) $request->get('days'), [1, 7, 30], true) ? (int) $request->get('days') : 7;
        $logs = \App\Models\CustomerSignalLog::where('customer_id', $customer->id)
            ->where('created_at', '>=', now()->subDays($days))->orderBy('created_at')->get(['rx_power', 'status', 'created_at']);
        $rx = $logs->pluck('rx_power')->filter(fn ($v) => $v !== null);
        $summary = [
            'min' => $rx->min(), 'max' => $rx->max(), 'avg' => $rx->count() ? round($rx->avg(), 2) : null,
            'down_events' => $logs->filter(fn ($l, $i) => in_array($l->status, NocService::DOWN_STATUSES, true)
                && ($i === 0 || ! in_array($logs[$i - 1]->status, NocService::DOWN_STATUSES, true)))->count(),
        ];

        return view('noc.history', compact('customer', 'logs', 'days', 'summary'));
    }
}
