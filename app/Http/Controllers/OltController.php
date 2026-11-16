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
    public function index()
    {
        $olts = Olt::withCount('ponPorts')->get();
        return view('olts.index', compact('olts'));
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
            'snmp_version' => 'required|integer|in:1,2',
            'snmp_read_community' => 'required|string',
            'snmp_write_community' => 'nullable|string',
            'telnet_port' => 'nullable|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'olt_type' => 'required|string',
            'access_method' => 'nullable|in:snmp_telnet,snmp',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['snmp_write_community'] = $validated['snmp_write_community'] ?? 'private';
        $validated['telnet_port'] = $validated['telnet_port'] ?? 23;
        $validated['is_active'] = $request->has('is_active');
        $validated['access_method'] = $validated['access_method'] ?? Olt::ACCESS_SNMP_TELNET;
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
            'snmp_version' => 'required|integer|in:1,2',
            'snmp_read_community' => 'nullable|string',
            'snmp_write_community' => 'nullable|string',
            'telnet_port' => 'nullable|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'enable_password' => 'nullable|string',
            'olt_type' => 'required|string',
            'access_method' => 'nullable|in:snmp_telnet,snmp',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['telnet_port'] = $validated['telnet_port'] ?? 23;
        $validated['is_active'] = $request->has('is_active');

        if (blank($validated['access_method'] ?? null)) {
            unset($validated['access_method']);
        }

        // Kredensial & community tidak ditampilkan di form: kosong = tetap memakai yang lama
        foreach (['password', 'enable_password', 'snmp_read_community', 'snmp_write_community'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]);
            }
        }

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
     * Manual Delete ONU from OLT
     */
    public function manualDeleteOnu(Request $request, Olt $olt)
    {
        if (! $olt->usesTelnet()) {
            return back()->with('error', 'OLT ini memakai mode SNMP saja; hapus ONU harus dilakukan langsung di OLT.');
        }
        $data = $request->validate([
            'onu_index' => 'required|string|max:40',
            'onu_sn' => 'required|string|max:32|regex:/^[A-Za-z0-9]+$/',
        ], [], ['onu_index' => 'indeks ONU', 'onu_sn' => 'SN ONU']);

        if (! \App\Services\Network\ZteOids::parseOnuPosition($data['onu_index'])) {
            return back()->with('error', 'Format indeks ONU tidak dikenali. Contoh: 1/1/7:5 atau .1.1.7.5');
        }

        // ONU hanya dihapus bila SN di posisi itu sama dengan SN yang diisi (hasilnya tercatat di Log Aktivitas)
        \App\Jobs\DeprovisionOnuJob::dispatch($olt->id, $data['onu_index'], strtoupper($data['onu_sn']), $request->user()?->id);

        return back()->with('success', "Penghapusan ONU {$data['onu_sn']} di posisi {$data['onu_index']} diantrekan. Hasilnya tercatat di Log Aktivitas.");
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
