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
            'web_username' => 'nullable|string|max:64',
            'web_password' => 'nullable|string|max:128',
            'web_port' => 'nullable|integer|min:1|max:65535',
            'web_https' => 'nullable|boolean',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['snmp_write_community'] = $validated['snmp_write_community'] ?? 'private';
        $validated['is_active'] = $request->has('is_active');
        $validated['web_https'] = $request->boolean('web_https');
        $validated['web_port'] = $validated['web_port'] ?? ($validated['web_https'] ? 443 : 80);
        $this->ensureV3Credentials($validated);
        $olt = Olt::create($validated);

        \App\Models\ActivityLog::log('olt_created', "OLT '{$olt->name}' ({$olt->ip_address}) ditambahkan.", $olt);

        return redirect()->route('olts.index')->with('success', 'OLT added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Olt $olt)
    {
        // Port belum ada: deteksi otomatis sekali (port PON jarang berubah)
        if ($olt->ponPorts()->count() === 0) {
            try {
                foreach ((new OltGateway($olt))->discoverPonPorts() as $port) {
                    OltPonPort::updateOrCreate(
                        ['olt_id' => $olt->id, 'slot' => $port['slot'], 'pon_port' => $port['port']],
                        ['status' => $port['status'] ?? 'inactive', 'description' => $port['description'] ?? "Port {$port['slot']}/{$port['port']}"]
                    );
                }
            } catch (\Exception $e) {
                \Log::warning("Auto discover on page load failed for OLT {$olt->id}: " . $e->getMessage());
            }
        }

        // Status perangkat: dari cache NOC; "Perbarui" membaca langsung OLT ini lewat SNMP
        $statuses = \App\Services\Network\NocSnapshot::cachedStatuses();
        $status = $statuses[$olt->id] ?? null;
        if ($request->boolean('refresh') || ($status === null && $olt->is_active)) {
            try {
                $status = (new OltGateway($olt))->getOltStatus();
            } catch (\Throwable) {
                $status = ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0];
            }
            if ($statuses) {   // perbarui entri OLT ini di cache bersama tanpa menghapus OLT lain
                $statuses[$olt->id] = $status;
                \Illuminate\Support\Facades\Cache::put('noc_olt_stats', $statuses, \App\Services\Network\NocSnapshot::TTL);
            }
        }

        // Info perangkat dari web OLT HSGQ (firmware, SN) — disimpan 1 hari; gagal disimpan 30 menit agar halaman
        // tidak menunggu web OLT berulang kali
        $board = null;
        if (str_contains(strtoupper((string) $olt->olt_type), 'HSGQ') && filled($olt->web_username) && filled($olt->web_password)) {
            $key = "olt_board_{$olt->id}";
            if ($request->boolean('refresh')) {
                \Illuminate\Support\Facades\Cache::forget($key);
            }
            $board = \Illuminate\Support\Facades\Cache::get($key);
            if ($board === null) {
                try {
                    $board = (new \App\Services\Network\HsgqWebClient($olt, 4))->boardInfo();
                    \Illuminate\Support\Facades\Cache::put($key, $board, now()->addDay());
                } catch (\Throwable) {
                    $board = [];
                    \Illuminate\Support\Facades\Cache::put($key, $board, now()->addMinutes(30));
                }
            }
        }

        $olt->load(['ponPorts' => fn ($q) => $q->orderBy('slot')->orderBy('pon_port')]);
        $olt->loadCount('customers');

        return view('olts.show', compact('olt', 'status', 'board'));
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
            'web_username' => 'nullable|string|max:64',
            'web_password' => 'nullable|string|max:128',
            'web_port' => 'nullable|integer|min:1|max:65535',
            'web_https' => 'nullable|boolean',
            'olt_type' => 'required|string',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Kredensial & community tidak ditampilkan di form: kosong = tetap memakai yang lama
        foreach (['snmp_read_community', 'snmp_write_community', 'snmp_v3_auth_password', 'snmp_v3_priv_password', 'web_password'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]);
            }
        }

        $validated['web_https'] = $request->boolean('web_https');
        $validated['web_port'] = $validated['web_port'] ?? ($validated['web_https'] ? 443 : 80);
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

    /** Tes login API web OLT (HSGQ) — dipakai untuk registrasi/allow ONU dari aplikasi. */
    public function testWebLogin(Olt $olt)
    {
        $client = new \App\Services\Network\HsgqWebClient($olt);
        try {
            $client->login();
            $info = $client->boardInfo();
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => 'Login web OLT berhasil'
            . (isset($info['product_name']) ? " — {$info['product_name']}" : '') . (isset($info['fw_ver']) ? " (firmware {$info['fw_ver']})" : '') . '.']);
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
            'port_count' => 'required|integer|in:4,8,16',
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

        // Halaman tampil langsung; daftar ONU (+ pelanggan & deskripsi) dimuat di latar lewat getPortData,
        // yang memakai cache 10 menit bila ada (dulu scan SNMP berjalan sebelum halaman tampil)
        $olt->loadCount('ponPorts');
        $siblings = $olt->ponPorts()->orderBy('slot')->orderBy('pon_port')->get(['id', 'slot', 'pon_port', 'status']);

        return view('olts.show_port', ['olt' => $olt, 'port' => $port, 'siblings' => $siblings]);
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
        $onus = $request->has('fresh') ? null : \Illuminate\Support\Facades\Cache::get($key);
        if ($onus === null) {
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
        }

        [$onus, $descError] = $this->enrichOnus($olt, $port, $onus, $request->has('fresh'));

        return response()->json([
            'onus' => $onus,
            'status' => 'ready',
            'description_error' => $descError,
            'capabilities' => $this->onuCapabilities($olt),
        ]);
    }

    /** Aksi ONU yang tersedia untuk OLT ini (SNMP saja; HSGQ menunggu API web teruji). */
    private function onuCapabilities(Olt $olt): array
    {
        $isHsgq = str_contains(strtoupper((string) $olt->olt_type), 'HSGQ');

        return [
            'reboot' => ! $isHsgq,
            'reboot_hint' => $isHsgq ? 'Reboot ONU HSGQ belum tersedia: SNMP HSGQ tidak mendukungnya; menunggu API web OLT diuji.' : null,
            'edit' => false,
            'edit_hint' => $isHsgq
                ? 'Ubah nama/deskripsi ONU HSGQ menunggu API web OLT diuji (isi login web di Ubah OLT).'
                : 'Ubah nama/deskripsi ONU ZTE belum didukung lewat SNMP.',
            'web_configured' => $isHsgq && filled($olt->web_username) && filled($olt->web_password),
        ];
    }

    /**
     * Tambahkan pelanggan yang tertaut (cocok SN/MAC) dan — untuk HSGQ dengan login web — deskripsi ONU dari
     * API web OLT (SNMP HSGQ tidak memuat deskripsi). Deskripsi bersifat tambahan: gagal dibaca tidak
     * menggagalkan daftar ONU.
     */
    private function enrichOnus(Olt $olt, \App\Models\OltPonPort $port, array $onus, bool $fresh): array
    {
        $norm = fn ($sn) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $sn));
        $customers = \App\Models\Customer::where('olt_id', $olt->id)->whereNotNull('onu_sn')
            ->get(['id', 'customer_code', 'name', 'onu_sn', 'status'])->keyBy(fn ($c) => $norm($c->onu_sn));

        $descriptions = [];
        $descError = null;
        if (str_contains(strtoupper((string) $olt->olt_type), 'HSGQ') && filled($olt->web_username) && filled($olt->web_password)) {
            $dKey = "olt_port_desc_{$port->id}";
            if ($fresh) {
                \Illuminate\Support\Facades\Cache::forget($dKey);
            }
            $descriptions = \Illuminate\Support\Facades\Cache::get($dKey);
            if ($descriptions === null) {
                try {
                    $descriptions = (new \App\Services\Network\HsgqWebClient($olt))->onuDetails((int) $port->pon_port);
                    \Illuminate\Support\Facades\Cache::put($dKey, $descriptions, now()->addMinutes(10));
                } catch (\Throwable $e) {
                    $descriptions = [];
                    $descError = 'Deskripsi tidak terbaca dari web OLT: ' . $e->getMessage();
                }
            }
        }

        foreach ($onus as &$onu) {
            $key = $norm($onu['sn'] ?? '');
            $c = $customers[$key] ?? null;
            $onu['customer'] = $c ? ['id' => $c->id, 'code' => $c->customer_code, 'name' => $c->name, 'status' => $c->status,
                'url' => route('customers.show', $c->id)] : null;
            $web = $descriptions[$key] ?? null;
            if ($web) {
                if (($web['description'] ?? '') !== '') {
                    $onu['description'] = $web['description'];
                }
                // Status dari web OLT pasti (SNMP hanya menebak dari ada/tidaknya data optik)
                if (! empty($web['status'])) {
                    $onu['status'] = $web['status'];
                }
                foreach (['last_down_reason', 'last_down_time', 'tx_power', 'temperature', 'voltage', 'bias', 'vendor'] as $f) {
                    if (isset($web[$f])) {
                        $onu[$f] = $web[$f];
                    }
                }
                if (! empty($web['register_time'])) {
                    $onu['reg_time'] = $web['register_time'];
                }
                if (! empty($web['onu_type'])) {
                    $onu['type'] = trim(($web['vendor'] ?? '') . ' ' . $web['onu_type']);
                }
            }
            if (($onu['description'] ?? '-') === '-') {
                $onu['description'] = '';
            }
        }
        unset($onu);

        return [$onus, $descError];
    }

    /**
     * Reboot satu ONU. ZTE: SNMP SET setelah SN di posisi itu dicocokkan (tidak me-reboot ONU pelanggan lain).
     * HSGQ: belum didukung (SNMP HSGQ tidak menyediakan reboot; menunggu API web OLT diuji).
     */
    public function rebootOnu(Request $request, Olt $olt, \App\Models\OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);
        $data = $request->validate(['index' => 'required|string|max:40', 'sn' => 'required|string|max:40']);
        $caps = $this->onuCapabilities($olt);
        if (! $caps['reboot']) {
            return response()->json(['success' => false, 'message' => $caps['reboot_hint']], 422);
        }
        $pos = \App\Services\Network\ZteOids::parseOnuPosition($data['index']);
        if (! $pos) {
            return response()->json(['success' => false, 'message' => 'Format posisi ONU tidak dikenali.'], 422);
        }

        $gateway = app()->makeWith(OltGateway::class, ['olt' => $olt]);
        $check = $gateway->verifyOnuSerial($pos['shelf'], $pos['slot'], $pos['port'], $pos['onu_id'], strtoupper($data['sn']));
        if (! $check['ok']) {
            return response()->json(['success' => false, 'message' => 'Reboot dibatalkan: ' . $check['reason']], 422);
        }
        $ok = $gateway->rebootOnu("{$pos['shelf']}.{$pos['slot']}.{$pos['port']}.{$pos['onu_id']}");
        \App\Models\ActivityLog::log($ok ? 'onu_rebooted' : 'onu_reboot_failed',
            ($ok ? 'ONU di-reboot' : 'Reboot ONU GAGAL') . " di {$olt->name}: posisi {$data['index']}, SN {$data['sn']}.", $olt);

        return response()->json(['success' => $ok, 'message' => $ok ? 'Perintah reboot terkirim ke ONU.' : 'OLT menolak perintah reboot (periksa write community/SNMPv3).'], $ok ? 200 : 502);
    }

    /**
     * Daftar ONU di semua port OLT (dibaca lewat SNMP) untuk dipilih di wizard aktivasi: teknisi menautkan ONU
     * yang sudah terdaftar/di-allow di OLT ke pelanggan tanpa mengetik SN & posisi. ONU yang sudah dipakai
     * pelanggan lain ditandai.
     */
    public function onuPicker(Request $request, Olt $olt)
    {
        $norm = fn (?string $sn) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $sn));
        $linked = \App\Models\Customer::where('olt_id', $olt->id)->whereNotNull('onu_sn')
            ->get(['id', 'customer_code', 'name', 'onu_sn'])->keyBy(fn ($c) => $norm($c->onu_sn));
        $forCustomer = (int) $request->query('customer_id');

        $gateway = new OltGateway($olt);
        $onus = [];
        $failedPorts = [];
        foreach ($olt->ponPorts()->orderBy('slot')->orderBy('pon_port')->get() as $port) {
            $key = "olt_port_data_{$port->id}";
            $rows = \Illuminate\Support\Facades\Cache::get($key);
            if ($rows === null || $request->boolean('fresh')) {
                try {
                    $rows = $gateway->getOnusOnPort($port->shelf ?: 1, $port->slot, $port->pon_port);
                    \Illuminate\Support\Facades\Cache::put($key, $rows, now()->addMinutes(10));
                } catch (\Throwable $e) {
                    $failedPorts[] = "{$port->slot}/{$port->pon_port}";
                    continue;
                }
            }
            foreach ($rows as $row) {
                $pos = \App\Services\Network\ZteOids::parseOnuPosition((string) ($row['index'] ?? ''));
                $owner = $linked[$norm($row['sn'] ?? '')] ?? null;
                $onus[] = [
                    'port' => "{$port->slot}/{$port->pon_port}",
                    'index' => $pos ? "{$pos['shelf']}/{$pos['slot']}/{$pos['port']}:{$pos['onu_id']}" : (string) ($row['index'] ?? ''),
                    'sn' => strtoupper((string) ($row['sn'] ?? '')),
                    'name' => (string) ($row['name'] ?? ''),
                    'status' => (string) ($row['status'] ?? ''),
                    'signal' => is_numeric($row['signal'] ?? null) ? (float) $row['signal'] : null,
                    'distance' => $row['distance'] ?? null,
                    'customer' => $owner ? ['code' => $owner->customer_code, 'name' => $owner->name, 'self' => $owner->id === $forCustomer] : null,
                ];
            }
        }

        return response()->json(['onus' => $onus, 'failed_ports' => $failedPorts, 'olt' => $olt->name]);
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
