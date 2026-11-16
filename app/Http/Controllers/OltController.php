<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Services\Network\OltGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OltController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $olts = Olt::withCount(['ponPorts', 'customers'])->orderBy('name')->get();

        // Status perangkat dari cache NOC (tanpa SNMP saat halaman dibuka); "Perbarui status" memeriksa langsung
        $statuses = $request->boolean('refresh')
            ? \App\Services\Network\NocSnapshot::oltStatuses($olts->where('is_active', true), true)
            : \App\Services\Network\NocSnapshot::cachedStatuses();
        $checkedAt = \App\Services\Network\NocSnapshot::cachedAt();

        // Ringkasan ONU per OLT dari cache sinyal (diisi pemantauan otomatis / halaman port)
        $onuStats = \App\Models\CustomerSignalCache::query()
            ->join('customers', 'customers.id', '=', 'customer_signal_caches.customer_id')
            ->whereNotNull('customers.olt_id')
            ->groupBy('customers.olt_id')
            ->selectRaw("customers.olt_id,
                SUM(CASE WHEN customer_signal_caches.status = 'online' THEN 1 ELSE 0 END) AS online,
                SUM(CASE WHEN customer_signal_caches.status <> 'online' THEN 1 ELSE 0 END) AS offline,
                SUM(CASE WHEN customer_signal_caches.rx_power < -27 THEN 1 ELSE 0 END) AS critical")
            ->get()->keyBy('olt_id');

        $summary = [
            'total' => $olts->count(),
            'active' => $olts->where('is_active', true)->count(),
            'online' => collect($statuses)->filter(fn ($s) => ($s['status'] ?? null) === 'online')->count(),
            'checked' => ! empty($statuses),
            'customers' => (int) $olts->sum('customers_count'),
            'critical' => (int) $onuStats->sum('critical'),
        ];

        return view('olts.index', compact('olts', 'statuses', 'checkedAt', 'onuStats', 'summary'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('olts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'snmp_port' => 'required|integer',
            'snmp_version' => 'required|integer|in:1,2,3',
            'snmp_read_community' => 'required_unless:snmp_version,3|nullable|string',
            'snmp_write_community' => 'nullable|string',
            'snmp_v3_user' => 'nullable|required_if:snmp_version,3|string|max:64',
            'snmp_v3_level' => 'nullable|required_if:snmp_version,3|in:noAuthNoPriv,authNoPriv,authPriv',
            'snmp_v3_auth_protocol' => 'nullable|in:MD5,SHA,SHA256',
            'snmp_v3_auth_password' => 'nullable|string|min:8|max:128',
            'snmp_v3_priv_protocol' => 'nullable|in:DES,AES',
            'snmp_v3_priv_password' => 'nullable|string|min:8|max:128',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['snmp_write_community'] = $validated['snmp_write_community'] ?? 'private';
        $validated['is_active'] = $request->has('is_active');
        $this->ensureV3Credentials($validated);
        $olt = Olt::create($validated);

        \App\Models\ActivityLog::log('olt_created', "OLT '{$olt->name}' ({$olt->ip_address}) ditambahkan.", $olt);

        return redirect()->route('olts.index')->with('success', 'OLT added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Olt $olt)
    {
        // Auto-discover and create ports on first view if none exist
        if ($olt->ponPorts()->count() === 0) {
            try {
                $gateway = new OltGateway($olt);
                $ports = $gateway->discoverPonPorts();
                foreach ($ports as $port) {
                    OltPonPort::updateOrCreate(
                        [
                            'olt_id' => $olt->id,
                            'slot' => $port['slot'],
                            'pon_port' => $port['port']
                        ],
                        [
                            'status' => $port['status'] ?? 'inactive',
                            'description' => $port['description'] ?? "Port {$port['slot']}/{$port['port']}"
                        ]
                    );
                }
            } catch (\Exception $e) {
                \Log::warning("Auto discover on page load failed for OLT {$olt->id}: " . $e->getMessage());
            }
        }

        $olt->load('ponPorts');
        return view('olts.show', compact('olt'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Olt $olt)
    {
        return view('olts.edit', compact('olt'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'snmp_port' => 'required|integer',
            'snmp_version' => 'required|integer|in:1,2,3',
            'snmp_read_community' => 'nullable|string',
            'snmp_write_community' => 'nullable|string',
            'snmp_v3_user' => 'nullable|required_if:snmp_version,3|string|max:64',
            'snmp_v3_level' => 'nullable|required_if:snmp_version,3|in:noAuthNoPriv,authNoPriv,authPriv',
            'snmp_v3_auth_protocol' => 'nullable|in:MD5,SHA,SHA256',
            'snmp_v3_auth_password' => 'nullable|string|min:8|max:128',
            'snmp_v3_priv_protocol' => 'nullable|in:DES,AES',
            'snmp_v3_priv_password' => 'nullable|string|min:8|max:128',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Kredensial & community tidak ditampilkan di form: kosong = tetap memakai yang lama
        foreach (['snmp_read_community', 'snmp_write_community', 'snmp_v3_auth_password', 'snmp_v3_priv_password'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]);
            }
        }

        $this->ensureV3Credentials($validated, $olt);
        $olt->update($validated);

        \App\Models\ActivityLog::log('olt_updated', "OLT '{$olt->name}' ({$olt->ip_address}) diperbarui.", $olt);

        return redirect()->route('olts.index')->with('success', 'OLT berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Olt $olt)
    {
        // Tolak bila masih ada pelanggan yang terhubung (dulu tautan OLT pelanggan hilang diam-diam)
        $linked = $olt->customers()->count();
        if ($linked > 0) {
            return back()->with('error', "OLT '{$olt->name}' masih dipakai {$linked} pelanggan. Pindahkan pelanggan ke OLT lain terlebih dahulu.");
        }

        $name = $olt->name;
        $olt->delete();

        \App\Models\ActivityLog::log('olt_deleted', "OLT '{$name}' dihapus.");

        return redirect()->route('olts.index')->with('success', 'OLT berhasil dihapus.');
    }

    /**
     * SNMPv3: password autentikasi wajib untuk level authNoPriv/authPriv, password enkripsi untuk authPriv
     * (boleh kosong saat ubah bila sudah tersimpan).
     */
    private function ensureV3Credentials(array $data, ?Olt $olt = null): void
    {
        if ((int) ($data['snmp_version'] ?? 0) !== 3) {
            return;
        }
        $level = $data['snmp_v3_level'] ?? 'authPriv';
        $errors = [];
        if (in_array($level, ['authNoPriv', 'authPriv'], true) && blank($data['snmp_v3_auth_password'] ?? $olt?->snmp_v3_auth_password)) {
            $errors['snmp_v3_auth_password'] = 'Password autentikasi SNMPv3 wajib diisi (minimal 8 karakter).';
        }
        if ($level === 'authPriv' && blank($data['snmp_v3_priv_password'] ?? $olt?->snmp_v3_priv_password)) {
            $errors['snmp_v3_priv_password'] = 'Password enkripsi SNMPv3 wajib diisi (minimal 8 karakter).';
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /**
     * Test SNMP Connection.
     */
    public function testConnection(Olt $olt)
    {
        $gateway = new OltGateway($olt);
        $snmpResult = $gateway->testSnmpConnection();

        return response()->json([
            'success' => $snmpResult['status'],
            'message' => $snmpResult['message'],
            'results' => [
                'snmp' => [
                    'success' => $snmpResult['status'],
                    'message' => $snmpResult['message'],
                    'device_name' => $snmpResult['device_name'] ?? 'Unknown'
                ]
            ]
        ]);
    }

    /**
     * Generate PON Ports automatically.
     */
    public function generatePorts(Request $request, Olt $olt)
    {
        $request->validate([
            'slot' => 'required|integer',
            'port_count' => 'required|integer|in:8,16',
        ]);

        $slot = $request->slot;
        $count = $request->port_count;

        DB::beginTransaction();
        try {
            for ($i = 1; $i <= $count; $i++) {
                OltPonPort::updateOrCreate(
                    ['olt_id' => $olt->id, 'slot' => $slot, 'pon_port' => $i],
                    ['status' => 'active']
                );
            }
            DB::commit();
            return back()->with('success', "Generated {$count} PON ports for Slot {$slot}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Error generating ports: " . $e->getMessage());
        }
    }

    /**
     * Auto Discover PON Ports via SNMP.
     */
    public function autoDiscoverPorts(Olt $olt)
    {
        try {
            $gateway = new OltGateway($olt);
            $ports = $gateway->discoverPonPorts();

            if (empty($ports)) {
                return back()->with('error', 'Tidak ada port PON yang terdeteksi lewat SNMP. Periksa community SNMP dan pastikan OLT terjangkau.');
            }

            // Reset status di DALAM transaksi (dulu sebelum transaksi: bila gagal di tengah, semua port
            // terlanjur nonaktif)
            DB::beginTransaction();
            OltPonPort::where('olt_id', $olt->id)->update(['status' => 'inactive']);
            foreach ($ports as $port) {
                OltPonPort::updateOrCreate(
                    [
                        'olt_id' => $olt->id,
                        'slot' => $port['slot'],
                        'pon_port' => $port['port']
                    ],
                    [
                        'status' => $port['status'] ?? 'inactive',
                        'description' => $port['description'] ?? "Port {$port['slot']}/{$port['port']}"
                    ]
                );
            }
            DB::commit();

            return back()->with('success', count($ports) . ' port PON terdeteksi lewat SNMP.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Deteksi port gagal: ' . $e->getMessage());
        }
    }

    /**
     * Show details for a specific PON port (Scan ONUs via SNMP immediately)
     */
    public function showPort(Olt $olt, OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);   // dulu port OLT lain bisa dibuka lewat URL

        // Tampilkan dari cache bila ada; bila belum, halaman memuat data di latar lewat getPortData
        // (dulu scan SNMP berjalan sebelum halaman tampil — lambat/timeout)
        $cached = \Illuminate\Support\Facades\Cache::get("olt_port_data_{$port->id}");

        return view('olts.show_port', [
            'olt' => $olt,
            'port' => $port,
            'onus' => $cached ?? [],
            'isLoading' => $cached === null,
        ]);
    }

    /**
     * Sync all PON ports status for an OLT
     */
    public function syncAllPorts(Olt $olt)
    {
        $ports = $olt->ponPorts;

        foreach ($ports as $index => $port) {
            // Dispatch with a slight delay for each port to avoid OLT lockout
            \App\Jobs\FetchOltPortDataJob::dispatch($olt, $port)
                ->delay(now()->addSeconds($index * 2));
        }

        return back()->with('success', "Syncing status for " . count($ports) . " ports in background. This may take a few minutes.");
    }

    public function getPortData(Request $request, Olt $olt, \App\Models\OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);

        $key = "olt_port_data_{$port->id}";
        if (! $request->has('fresh') && ($cached = \Illuminate\Support\Facades\Cache::get($key)) !== null) {
            return response()->json(['onus' => $cached, 'status' => 'ready']);
        }

        try {
            $onus = (new OltGateway($olt))->getOnusOnPort($port->shelf ?: 1, $port->slot, $port->pon_port);
        } catch (\Throwable $e) {
            // Gagal scan TIDAK disimpan sebagai port kosong & status port tidak diubah (dulu di-cache 5 menit)
            \Illuminate\Support\Facades\Log::warning("Scan port OLT {$olt->id} {$port->slot}/{$port->pon_port} gagal: " . $e->getMessage());

            return response()->json(['onus' => [], 'status' => 'error', 'message' => 'OLT tidak merespons SNMP. Coba lagi.']);
        }

        \Illuminate\Support\Facades\Cache::put($key, $onus, now()->addMinutes(10));
        $port->update(['status' => count($onus) > 0 ? 'active' : 'inactive']);
        $this->syncSignalCache($olt, $onus);

        return response()->json(['onus' => $onus, 'status' => 'ready']);
    }

    /** Perbarui cache sinyal NOC & indeks ONU pelanggan dari hasil scan port. */
    private function syncSignalCache(Olt $olt, array $onus): void
    {
        foreach ($onus as $onuData) {
            if (empty($onuData['sn'])) {
                continue;
            }
            $customer = \App\Models\Customer::where('olt_id', $olt->id)->where('onu_sn', $onuData['sn'])->first();
            if (! $customer) {
                continue;
            }
            \App\Models\CustomerSignalCache::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'onu_index' => $onuData['index'],
                    'rx_power' => is_numeric($onuData['signal'] ?? null) ? $onuData['signal'] : null,
                    'status' => $onuData['status'] ?? null,
                    'last_polled_at' => now(),
                ]
            );
            if (($onuData['status'] ?? null) === 'online' && $customer->onu_index !== $onuData['index']) {
                $customer->update(['onu_index' => $onuData['index']]);
            }
        }
    }
}
