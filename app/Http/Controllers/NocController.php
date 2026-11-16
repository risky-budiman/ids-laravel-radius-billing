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
