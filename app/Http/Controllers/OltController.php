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

        $workCounts = \App\Models\OltWorkItem::pending()->selectRaw('olt_id, COUNT(*) AS n')->groupBy('olt_id')->pluck('n', 'olt_id');

        return view('olts.index', compact('olts', 'statuses', 'checkedAt', 'onuStats', 'summary', 'workCounts'));
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
        // Tidak membaca SNMP otomatis saat halaman dibuka: OLT yang mati membuat halaman menunggu timeout
        if ($request->boolean('refresh')) {
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

        // Pekerjaan manual di OLT: tutup otomatis yang sudah terlihat dari hasil pindai tersimpan (tanpa SNMP)
        app(\App\Services\Network\OltWorkVerifier::class)->verify($olt, cacheOnly: true);
        $work = \App\Models\OltWorkItem::with(['customer:id,customer_code,name', 'resolver:id,name'])->where('olt_id', $olt->id)
            ->where(fn ($q) => $q->where('status', 'pending')->orWhere('resolved_at', '>=', now()->subDays(3)))
            ->orderByRaw("status = 'pending' desc")->latest('id')->limit(50)->get();

        return view('olts.show', compact('olt', 'status', 'board', 'work'));
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

    /** Cek pekerjaan manual langsung ke OLT (SNMP) & tutup yang sudah dikerjakan. */
    public function verifyWork(Olt $olt)
    {
        $r = app(\App\Services\Network\OltWorkVerifier::class)->verify($olt);
        $msg = $r['done'] ? "{$r['done']} pekerjaan terkonfirmasi selesai di OLT." : 'Belum ada pekerjaan yang terkonfirmasi di OLT.';
        if ($r['ports_read'] < $r['ports_total']) {
            $msg .= " ({$r['ports_read']}/{$r['ports_total']} port terbaca; pekerjaan hapus ONU baru dicek bila semua port terbaca.)";
        }

        return back()->with('success', $msg);
    }

    /** Tandai pekerjaan selesai / abaikan (siapa & kapan dicatat). */
    public function resolveWork(Request $request, Olt $olt, \App\Models\OltWorkItem $item)
    {
        abort_unless((int) $item->olt_id === (int) $olt->id, 404);
        $data = $request->validate(['action' => 'required|in:done,dismiss', 'note' => 'nullable|string|max:500']);
        if ($item->status !== 'pending') {
            return back()->with('error', 'Pekerjaan ini sudah ditutup.');
        }
        $item->update(['status' => $data['action'] === 'done' ? 'done' : 'dismissed', 'resolution' => 'manual',
            'resolved_by' => $request->user()->id, 'resolved_at' => now(), 'note' => $data['note'] ?? $item->note]);
        \App\Models\ActivityLog::log('olt_work_' . ($data['action'] === 'done' ? 'done' : 'dismissed'),
            "{$item->typeLabel()} {$item->onu_sn} di {$olt->name} ditandai " . ($data['action'] === 'done' ? 'selesai' : 'diabaikan') . '.', $olt);

        return back()->with('success', 'Pekerjaan ditandai ' . ($data['action'] === 'done' ? 'selesai.' : 'diabaikan.'));
    }

    /** Tes login API web OLT (HSGQ) — dipakai untuk registrasi/allow ONU dari aplikasi. */
    /**
     * Simpan konfigurasi OLT HSGQ ke flash setelah perubahan. Gagal simpan tidak membatalkan perubahan yang
     * sudah diterapkan, tetapi ditandai "belum tersimpan" sampai berhasil disimpan.
     *
     * @return string kalimat tambahan untuk pesan ke pengguna
     */
    private function persistHsgq(Olt $olt, \App\Services\Network\HsgqWebClient $web): string
    {
        try {
            $web->saveConfig();
        } catch (\RuntimeException $e) {
            \Illuminate\Support\Facades\Cache::forever("olt_unsaved_{$olt->id}", time());
            \App\Models\ActivityLog::log('olt_config_save_failed', "Simpan konfigurasi OLT {$olt->name} GAGAL: {$e->getMessage()}", $olt);

            return ' PERHATIAN: konfigurasi OLT belum tersimpan (' . $e->getMessage() . '). Tekan "Simpan konfigurasi" di halaman OLT agar tidak hilang saat OLT restart.';
        }
        \Illuminate\Support\Facades\Cache::forget("olt_unsaved_{$olt->id}");
        \Illuminate\Support\Facades\Cache::forever("olt_saved_at_{$olt->id}", time());

        return ' Konfigurasi OLT tersimpan.';
    }

    /** Tombol "Simpan konfigurasi" (HSGQ dengan akses web). */
    public function saveConfig(Olt $olt)
    {
        $web = $this->hsgqWebOr422($olt);
        if ($web instanceof \Illuminate\Http\JsonResponse) {
            return back()->with('error', $web->getData(true)['message']);
        }
        $note = trim($this->persistHsgq($olt, $web));
        $ok = ! \Illuminate\Support\Facades\Cache::has("olt_unsaved_{$olt->id}");
        if ($ok) {
            \App\Models\ActivityLog::log('olt_config_saved', "Konfigurasi OLT {$olt->name} disimpan ke flash.", $olt);
        }

        return back()->with($ok ? 'success' : 'error', $ok ? 'Konfigurasi OLT tersimpan ke flash.' : $note);
    }

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

    /** Aksi ONU yang tersedia untuk OLT ini. ZTE: reboot lewat SNMP. HSGQ: semua aksi lewat API web OLT. */
    private function onuCapabilities(Olt $olt): array
    {
        $isHsgq = $this->isHsgq($olt);
        $web = $isHsgq && filled($olt->web_username) && filled($olt->web_password);
        $needWeb = 'Butuh akses web OLT (Ubah OLT → Akses web OLT).';

        return [
            'reboot' => ! $isHsgq || $web,
            'reboot_hint' => $isHsgq && ! $web ? 'Reboot ONU HSGQ: ' . $needWeb : null,
            'edit' => $web,
            'edit_hint' => $web ? null : ($isHsgq ? 'Ubah nama/deskripsi ONU HSGQ: ' . $needWeb : 'Ubah nama/deskripsi ONU ZTE belum didukung lewat SNMP.'),
            'allow' => $web,
            'delete' => $web,
            'web_configured' => $web,
        ];
    }

    private function isHsgq(Olt $olt): bool
    {
        return str_contains(strtoupper((string) $olt->olt_type), 'HSGQ');
    }

    /** Klien web HSGQ untuk aksi tulis, atau respons 422 bila OLT ini tidak mendukungnya. */
    private function hsgqWebOr422(Olt $olt): \App\Services\Network\HsgqWebClient|\Illuminate\Http\JsonResponse
    {
        if (! $this->isHsgq($olt) || ! filled($olt->web_username) || ! filled($olt->web_password)) {
            return response()->json(['success' => false, 'message' => 'Aksi ini butuh OLT HSGQ dengan akses web OLT terisi.'], 422);
        }

        return new \App\Services\Network\HsgqWebClient($olt, 15);
    }

    /** Nomor ONU (onu_id) dari posisi "1/1/2:5" atau "1.1.2.5". */
    private function onuIdFromIndex(string $index): ?int
    {
        $parts = array_values(array_filter(preg_split('/[.\/:]/', $index), 'strlen'));

        return count($parts) >= 4 && ctype_digit($parts[3]) ? (int) $parts[3] : null;
    }

    /** Hapus cache daftar & deskripsi ONU port agar layar menampilkan hasil perintah. */
    private function forgetPortCache(\App\Models\OltPonPort $port): void
    {
        \Illuminate\Support\Facades\Cache::forget("olt_port_data_{$port->id}");
        \Illuminate\Support\Facades\Cache::forget("olt_port_desc_{$port->id}");
    }

    /** Tutup pekerjaan OLT yang menunggu untuk SN ini (setelah dikerjakan dari aplikasi). */
    private function closeWorkFor(Olt $olt, string $type, string $sn, string $note): void
    {
        $want = \App\Models\OltWorkItem::normalizeSn($sn);
        \App\Models\OltWorkItem::pending()->where('olt_id', $olt->id)->where('type', $type)->get()
            ->filter(fn ($w) => \App\Models\OltWorkItem::normalizeSn($w->onu_sn) === $want)
            ->each(fn ($w) => $w->update(['status' => 'done', 'resolution' => 'manual', 'resolved_by' => auth()->id(),
                'resolved_at' => now(), 'note' => $note]));
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
        if ($this->isHsgq($olt)) {
            return $this->hsgqOnuAction($olt, $port, $data['index'], $data['sn'], 'reboot', function ($web, $onuId) use ($port) {
                $web->rebootOnu((int) $port->pon_port, $onuId);

                return 'Perintah reboot terkirim ke ONU.';
            });
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
     * Jalankan perintah HSGQ ke ONU yang sudah ada: posisi & MAC dicocokkan dulu di OLT, hasil dicatat di log.
     */
    private function hsgqOnuAction(Olt $olt, \App\Models\OltPonPort $port, string $index, string $sn, string $action, \Closure $run)
    {
        $web = $this->hsgqWebOr422($olt);
        if ($web instanceof \Illuminate\Http\JsonResponse) {
            return $web;
        }
        $onuId = $this->onuIdFromIndex($index);
        if (! $onuId) {
            return response()->json(['success' => false, 'message' => 'Format posisi ONU tidak dikenali.'], 422);
        }
        $labels = ['reboot' => 'Reboot ONU', 'rename' => 'Ubah nama/deskripsi ONU', 'delete' => 'Hapus ONU'];
        try {
            $web->verifyOnu((int) $port->pon_port, $onuId, $sn);
            $message = $run($web, $onuId);
        } catch (\RuntimeException $e) {
            \App\Models\ActivityLog::log("onu_{$action}_failed", "{$labels[$action]} GAGAL di {$olt->name}: posisi {$index}, MAC {$sn}. {$e->getMessage()}", $olt);

            return response()->json(['success' => false, 'message' => $labels[$action] . ' dibatalkan: ' . $e->getMessage()], 422);
        }
        $this->forgetPortCache($port);
        \App\Models\ActivityLog::log("onu_{$action}", "{$labels[$action]} di {$olt->name}: posisi {$index}, MAC " . strtoupper($sn) . '.', $olt);
        if ($action !== 'reboot') {   // reboot tidak mengubah konfigurasi
            $message .= $this->persistHsgq($olt, $web);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /** Ubah nama & deskripsi ONU (HSGQ, API web OLT). */
    public function updateOnuInfo(Request $request, Olt $olt, \App\Models\OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);
        $data = $request->validate([
            'index' => 'required|string|max:40', 'sn' => 'required|string|max:40',
            'name' => ['nullable', 'string', 'max:16', 'regex:/^\S*$/'], 'description' => 'nullable|string|max:64',
        ], ['name.regex' => 'Nama ONU tidak boleh berisi spasi.']);

        return $this->hsgqOnuAction($olt, $port, $data['index'], $data['sn'], 'rename', function ($web, $onuId) use ($port, $data) {
            $web->setOnuInfo((int) $port->pon_port, $onuId, (string) ($data['name'] ?? ''), (string) ($data['description'] ?? ''));

            return 'Nama & deskripsi ONU tersimpan di OLT.';
        });
    }

    /** Hapus ONU dari daftar izin OLT (HSGQ). ONU langsung terputus. */
    public function deleteOnu(Request $request, Olt $olt, \App\Models\OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);
        $data = $request->validate(['index' => 'required|string|max:40', 'sn' => 'required|string|max:40']);

        $res = $this->hsgqOnuAction($olt, $port, $data['index'], $data['sn'], 'delete', function ($web, $onuId) use ($port, $data) {
            $web->deleteOnu((int) $port->pon_port, $onuId, $data['sn']);

            return 'ONU dihapus dari OLT.';
        });
        if ($res->getData(true)['success'] ?? false) {
            $this->closeWorkFor($olt, 'delete', $data['sn'], 'Dihapus dari aplikasi (API web OLT).');
        }

        return $res;
    }

    /** ONU di port yang menunggu izin (belum diizinkan / ada di daftar tolak) — HSGQ. */
    public function pendingOnus(Olt $olt, \App\Models\OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);
        $web = $this->hsgqWebOr422($olt);
        if ($web instanceof \Illuminate\Http\JsonResponse) {
            return $web;
        }
        try {
            return response()->json(['success' => true, 'onus' => $web->pendingOnus((int) $port->pon_port)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    /**
     * Allow (izinkan) ONU di port lewat API web OLT HSGQ. Pekerjaan "daftarkan ONU" untuk MAC ini ikut ditutup;
     * bila MAC milik pelanggan, nama & deskripsi default = kode & nama pelanggan.
     */
    public function allowOnu(Request $request, Olt $olt, \App\Models\OltPonPort $port)
    {
        abort_unless((int) $port->olt_id === (int) $olt->id, 404);
        $data = $request->validate([
            'mac' => 'required|string|max:40', 'name' => ['nullable', 'string', 'max:16', 'regex:/^\S*$/'],
            'description' => 'nullable|string|max:64',
        ], ['name.regex' => 'Nama ONU tidak boleh berisi spasi.']);
        $result = $this->doAllow($olt, $port, $data['mac'], $data['name'] ?? null, $data['description'] ?? null);

        return response()->json(['success' => $result['ok'], 'message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    /** @return array{ok: bool, message: string} */
    private function doAllow(Olt $olt, \App\Models\OltPonPort $port, string $mac, ?string $name, ?string $description): array
    {
        $web = $this->hsgqWebOr422($olt);
        if ($web instanceof \Illuminate\Http\JsonResponse) {
            return ['ok' => false, 'message' => $web->getData(true)['message']];
        }
        $macaddr = \App\Services\Network\HsgqWebClient::formatMac($mac);
        if (! $macaddr) {
            return ['ok' => false, 'message' => 'MAC ONU tidak valid (contoh: E8:68:19:02:FF:70).'];
        }
        $norm = \App\Models\OltWorkItem::normalizeSn($macaddr);
        $customer = \App\Models\Customer::whereNotNull('onu_sn')->get(['id', 'customer_code', 'name', 'onu_sn'])
            ->first(fn ($c) => \App\Models\OltWorkItem::normalizeSn($c->onu_sn) === $norm);
        $name = filled($name) ? $name : ($customer ? substr((string) $customer->customer_code, 0, 16) : '');
        $description = filled($description) ? $description : ($customer ? mb_substr($customer->name, 0, 64) : '');
        $pon = "PON {$port->slot}/{$port->pon_port}";

        try {
            $state = $web->allowOnu((int) $port->pon_port, $macaddr, $name, $description);
        } catch (\RuntimeException $e) {
            \App\Models\ActivityLog::log('onu_allow_failed', "Allow ONU GAGAL di {$olt->name} {$pon}: MAC " . strtoupper($macaddr) . ". {$e->getMessage()}", $olt);

            return ['ok' => false, 'message' => 'Allow ONU gagal: ' . $e->getMessage()];
        }
        $this->forgetPortCache($port);
        $message = ['added' => "ONU ditambahkan & diizinkan di {$pon}.", 'authorized' => "ONU diizinkan di {$pon}.",
            'already' => "ONU sudah diizinkan di {$pon}."][$state];
        \App\Models\ActivityLog::log('onu_allowed', "Allow ONU di {$olt->name} {$pon}: MAC " . strtoupper($macaddr)
            . ($customer ? " (pelanggan {$customer->customer_code} {$customer->name})" : '') . " — {$state}.", $olt);
        $this->closeWorkFor($olt, 'provision', $macaddr, "Di-allow dari aplikasi di {$pon}.");
        if ($state !== 'already') {
            $message .= ' ONU biasanya online ±1 menit kemudian.' . $this->persistHsgq($olt, $web);
        }

        return ['ok' => true, 'message' => $message];
    }

    /**
     * Kerjakan pekerjaan OLT langsung dari panel (HSGQ + akses web): "daftarkan" → allow di port yang dipilih;
     * "hapus" → cari MAC di semua port lalu hapus dari daftar izin.
     */
    public function runWork(Request $request, Olt $olt, \App\Models\OltWorkItem $item)
    {
        abort_unless((int) $item->olt_id === (int) $olt->id, 404);
        if ($item->status !== 'pending' || ! in_array($item->type, ['provision', 'delete'], true) || ! $item->onu_sn) {
            return back()->with('error', 'Pekerjaan ini tidak bisa dikerjakan dari aplikasi.');
        }

        if ($item->type === 'provision') {
            $data = $request->validate(['port_id' => 'required|integer']);
            $port = $olt->ponPorts()->whereKey($data['port_id'])->firstOrFail();
            $customer = $item->customer;
            $r = $this->doAllow($olt, $port, $item->onu_sn, $customer ? substr((string) $customer->customer_code, 0, 16) : null,
                $customer ? mb_substr($customer->name, 0, 64) : null);
            $item->refresh();
            if ($r['ok'] && $item->status === 'pending') {   // MAC beda format dari pekerjaan: tutup tetap
                $item->update(['status' => 'done', 'resolution' => 'manual', 'resolved_by' => auth()->id(), 'resolved_at' => now(),
                    'note' => "Di-allow dari aplikasi di PON {$port->slot}/{$port->pon_port}."]);
            }

            return back()->with($r['ok'] ? 'success' : 'error', $r['message']);
        }

        $web = $this->hsgqWebOr422($olt);
        if ($web instanceof \Illuminate\Http\JsonResponse) {
            return back()->with('error', $web->getData(true)['message']);
        }
        try {
            foreach ($olt->ponPorts()->orderBy('pon_port')->get() as $port) {
                $row = $web->findOnu((int) $port->pon_port, $item->onu_sn);
                if ($row) {
                    $web->deleteOnu((int) $port->pon_port, (int) $row['onu_id'], $item->onu_sn);
                    $this->forgetPortCache($port);
                    $item->update(['status' => 'done', 'resolution' => 'manual', 'resolved_by' => auth()->id(), 'resolved_at' => now(),
                        'note' => "Dihapus dari aplikasi (PON {$port->slot}/{$port->pon_port}:{$row['onu_id']})."]);
                    \App\Models\ActivityLog::log('onu_delete', "Hapus ONU di {$olt->name} PON {$port->slot}/{$port->pon_port}:{$row['onu_id']}, MAC {$item->onu_sn} (pekerjaan OLT).", $olt);

                    return back()->with('success', "ONU {$item->onu_sn} dihapus dari PON {$port->slot}/{$port->pon_port}." . $this->persistHsgq($olt, $web));
                }
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Hapus ONU gagal: ' . $e->getMessage());
        }
        $item->update(['status' => 'done', 'resolution' => 'auto', 'resolved_at' => now(), 'note' => 'Sudah tidak ada di daftar izin OLT.']);

        return back()->with('success', "ONU {$item->onu_sn} sudah tidak ada di OLT; pekerjaan ditutup.");
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
