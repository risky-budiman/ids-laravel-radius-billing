<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AcsServerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servers = \App\Models\AcsServer::all();
        return view('acs-servers.index', compact('servers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->serverRules());
        $validated['is_active'] = $request->boolean('is_active');

        \App\Models\AcsServer::create($validated);

        return redirect()->back()->with('success', 'Server ACS ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\AcsServer $acsServer)
    {
        $validated = $request->validate($this->serverRules());
        $validated['is_active'] = $request->boolean('is_active');
        // Password NBI kosong = tetap memakai yang tersimpan; centang "hapus login" untuk melepasnya
        if (! filled($validated['nbi_password'] ?? null)) {
            unset($validated['nbi_password']);
        }
        if ($request->boolean('clear_nbi_auth')) {
            $validated['nbi_username'] = null;
            $validated['nbi_password'] = null;
        }

        $acsServer->update($validated);

        return redirect()->back()->with('success', 'Server ACS diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    private function serverRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'nbi_username' => 'nullable|string|max:100',
            'nbi_password' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
    }

    /** Tes koneksi & login NBI: membaca 1 perangkat. */
    public function testConnection(\App\Models\AcsServer $acsServer)
    {
        try {
            $res = \App\Services\GenieACSService::forServer($acsServer)->getDevices([], ['_id'], 0, 1);
            $total = $res['total'] ?? null;

            return back()->with('success', "Terhubung ke {$acsServer->name}" . ($total !== null ? " ({$total} perangkat)" : '') . '.');
        } catch (\Throwable $e) {
            return back()->with('error', "Tidak bisa terhubung ke {$acsServer->name}: " . \Illuminate\Support\Str::limit($e->getMessage(), 200));
        }
    }

    public function destroy(\App\Models\AcsServer $acsServer)
    {
        $acsServer->delete();

        return redirect()->back()->with('success', 'ACS Server deleted successfully.');
    }

    /**
     * Display a listing of devices from an ACS server.
     */
    public function devices(Request $request)
    {
        $servers = \App\Models\AcsServer::where('is_active', true)->get();
        $serverId = $request->get('server_id');
        $search = $request->get('q');
        $tagFilter = $request->get('tag');
        $perPage = in_array((int)$request->get('limit'), [10, 25, 50, 100]) ? (int)$request->get('limit') : 25;
        $page = $request->get('page', 1);
        $skip = ($page - 1) * $perPage;

        $selectedServer = $serverId ? $servers->find($serverId) : $servers->first();
        $status = in_array($request->get('status'), ['online', 'offline'], true) ? $request->get('status') : null;
        $devices = [];
        $total = 0;
        $error = null;
        $stats = null;

        if ($selectedServer) {
            try {
                $service = \App\Services\GenieACSService::forServer($selectedServer);
                
                // Pencarian & filter tag: $regex yang di-escape (dulu string "/kata/i" = teks biasa di NBI)
                $query = \App\Services\GenieACSService::searchQuery($search, $tagFilter);
                // Filter status: online = inform terakhir < 15 menit
                $since = now()->subMinutes(\App\Support\AcsDevice::ONLINE_MINUTES)->utc()->format('Y-m-d\TH:i:s.v\Z');
                $statusQ = ['online' => ['_lastInform' => ['$gt' => $since]], 'offline' => ['_lastInform' => ['$lte' => $since]]][$status] ?? null;
                if ($statusQ) {
                    $query = $query ? ['$and' => [$query, $statusQ]] : $statusQ;
                }
                // Ringkasan seluruh server (2 permintaan ringan: hanya header "total")
                try {
                    $stats['total'] = (int) ($service->getDevices([], ['_id'], 0, 1)['total'] ?? 0);
                    $stats['online'] = (int) ($service->getDevices(['_lastInform' => ['$gt' => $since]], ['_id'], 0, 1)['total'] ?? 0);
                    $stats['offline'] = max(0, $stats['total'] - $stats['online']);
                } catch (\Exception) {
                    $stats = null;
                }

                $projection = [
                    '_id', 
                    '_tags',
                    'Tags',
                    '_lastInform', 
                    'DeviceID.SerialNumber',
                    'DeviceID.ProductClass',
                    'DeviceID.Manufacturer',
                    '_deviceId._SerialNumber',
                    '_deviceId._ProductClass',
                    '_deviceId._Manufacturer',
                    // Serial Number
                    'Device.DeviceInfo.SerialNumber',
                    'InternetGatewayDevice.DeviceInfo.SerialNumber',
                    // Product Class / Model
                    'Device.DeviceInfo.ProductClass',
                    'InternetGatewayDevice.DeviceInfo.ProductClass',
                    'Device.DeviceInfo.ModelName',
                    'InternetGatewayDevice.DeviceInfo.ModelName',
                    // WAN IP - Standard TR-069 paths
                    'Device.IP.Interface.1.IPv4Address.1.IPAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                    'Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                    'Device.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                    // PPPoE Username - Standard TR-069 paths
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username',
                    'Device.PPP.Interface.1.Username',
                    'Device.PPP.Interface.2.Username',
                    // Rx Power / Optical Signal - Vendor-specific standard paths
                    'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RXPower',
                    'InternetGatewayDevice.WANDevice.1.X_HW_OpticalInfo.RxPower',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_ZTE-COM_WANPONInterfaceConfig.RXPower',
                    'InternetGatewayDevice.WANDevice.1.X_ZTE-COM_Optical.RxPower',
                    'InternetGatewayDevice.X_ALU_OntOpticalParam.RXPower',
                    'Device.Optical.Interface.1.OpticalSignalLevel',
                    'Device.Optical.Interface.1.OpticalPowerRx',
                    'Device.DeviceInfo.Manufacturer',
                    'InternetGatewayDevice.DeviceInfo.Manufacturer',
                    'VirtualParameters',
                ];

                $response = $service->getDevices($query, $projection, $skip, $perPage);
                $devices = $response['data'];
                $apiTotal = $response['total'];
                
                if ($apiTotal === null || $apiTotal == 0) {
                    try {
                        // Perform a lightweight projection query for IDs to determine exact count
                        $countResponse = $service->getDevices($query, ['_id'], 0, 10000);
                        $total = count($countResponse['data'] ?? []);
                    } catch (\Exception $e) {
                        $total = count($devices);
                    }
                } else {
                    $total = $apiTotal;
                }
            } catch (\Exception $e) {
                $error = "Gagal membaca perangkat dari {$selectedServer->name}: " . \Illuminate\Support\Str::limit($e->getMessage(), 200);
            }
        }

        // Ringkasan per perangkat + pelanggan tertaut (username PPPoE, dengan atau tanpa @domain)
        $rows = \App\Support\AcsDevice::attachCustomers(collect($devices)->map(fn ($d) => \App\Support\AcsDevice::summarize((array) $d)));

        // Manual Pagination
        $paginatedDevices = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $total, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        return view('acs-servers.devices', compact('servers', 'selectedServer', 'paginatedDevices', 'error', 'search', 'tagFilter', 'status', 'stats'));
    }

    /**
     * Display the raw JSON of a device for debugging.
     */
    public function showDeviceRaw(Request $request, string $deviceId)
    {
        $serverId = $request->get('server_id');
        $server = \App\Models\AcsServer::findOrFail($serverId);
        
        try {
            $service = \App\Services\GenieACSService::forServer($server);
            $response = $service->getDevice($deviceId);
            
            return response()->json($response['data']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the structured device details page.
     */
    public function deviceDetails(Request $request, string $deviceId)
    {
        $serverId = $request->get('server_id');
        
        // Try to find the server, or fallback to the first one
        if ($serverId) {
            $server = \App\Models\AcsServer::find($serverId);
        } else {
            $server = \App\Models\AcsServer::first();
        }

        if (!$server) {
            return back()->with('error', "No ACS Server configured.");
        }
        
        try {
            $service = \App\Services\GenieACSService::forServer($server);
            $response = $service->getDevice($deviceId);
            $device = $response['data'];
            
            // Fetch tasks but don't crash if it fails
            try {
                $tasksResponse = $service->getTasks($deviceId);
                $tasks = $tasksResponse['data'] ?? [];
            } catch (\Exception $e) {
                $tasks = [];
                // Log the task fetching error but continue
                \Illuminate\Support\Facades\Log::warning("Could not fetch tasks for {$deviceId}: " . $e->getMessage());
            }
            
            $wanConnections = $service->extractWanConnections($device ?? []);
            $faults = $service->deviceFaults($deviceId);   // [task_id => alasan gagal]
            $summary = $device ? \App\Support\AcsDevice::attachCustomers(collect([\App\Support\AcsDevice::summarize((array) $device)]))->first() : null;

            return view('acs-servers.device-details', compact('device', 'server', 'deviceId', 'service', 'tasks', 'wanConnections', 'summary', 'faults'));
        } catch (\Exception $e) {
            return view('acs-servers.device-details', [
                'device' => null,
                'server' => $server,
                'deviceId' => $deviceId,
                'service' => isset($service) ? $service : null,
                'tasks' => [],
                'wanConnections' => [],
                'error' => "ACS Connection Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Push new configuration to the device.
     */
    public function updateConfig(Request $request, string $deviceId)
    {
        $serverId = $request->get('server_id');
        $instance = $request->get('instance', 1);
        $server = \App\Models\AcsServer::findOrFail($serverId);
        $service = \App\Services\GenieACSService::forServer($server);

        try {
            $deviceResponse = $service->getDevice($deviceId);
            $deviceData = $deviceResponse['data'] ?? [];
        } catch (\Exception $e) {
            $deviceData = [];
            \Illuminate\Support\Facades\Log::warning("Could not pre-fetch device schema for {$deviceId}: " . $e->getMessage());
        }

        $params = [];

        // 1. Process PPPoE / WAN parameters if present
        $hasWanInput = $request->filled('wan_path')
            || $request->has('is_enabled')
            || $request->filled('ppp_username') 
            || $request->filled('ppp_password') 
            || $request->filled('vlan_id') 
            || $request->filled('connection_trigger') 
            || $request->has('nat_enabled') 
            || $request->filled('mru') 
            || $request->filled('service_list')
            || $request->has('lan_bind')
            || $request->has('ssid_bind');

        $targetWanPath = null;
        if ($hasWanInput) {
            $wanResult = $service->buildPppoeParameters($deviceData, [
                'is_enabled' => $request->has('is_enabled') ? $request->input('is_enabled') : null,
                'ppp_username' => $request->ppp_username,
                'ppp_password' => $request->ppp_password,
                'vlan_id' => $request->vlan_id,
                'connection_trigger' => $request->connection_trigger,
                'nat_enabled' => $request->has('nat_enabled') ? $request->boolean('nat_enabled') : null,
                'mru' => $request->mru,
                'service_list' => $request->service_list,
                'lan_bind' => $request->input('lan_bind', []),
                'ssid_bind' => $request->input('ssid_bind', []),
            ], $request->wan_path);

            $params = array_merge($params, $wanResult['params']);
            $targetWanPath = $wanResult['target_path'] ?? null;
        }

        // 2. Process Wi-Fi parameters if present
        if ($request->filled('wifi_ssid') || $request->filled('wifi_password')) {
            $wifiParams = $service->buildWifiParameters($deviceData, [
                'wifi_ssid' => $request->wifi_ssid,
                'wifi_password' => $request->wifi_password,
            ], (int) $instance);

            $params = array_merge($params, $wifiParams);
        }

        if (empty($params)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Tidak ada perubahan konfigurasi yang dikirim."], 422);
            }
            return back()->with('error', "No changes to push.");
        }

        try {
            // Hanya parameter yang ada di modem yang dikirim; hasil sebenarnya (diterapkan / antre / ditolak)
            // dilaporkan. Dulu selalu "berhasil" walau modem menolak dan perintahnya tertahan di antrean.
            $result = $service->applyParameters($deviceId, $deviceData, $params);
            $ok = $result['state'] !== 'failed';
            $label = $request->filled('wifi_ssid') || $request->filled('wifi_password') ? "Wi-Fi {$instance}" : 'WAN/PPPoE';
            $msg = "{$label}: {$result['message']}";
            if ($ok && $result['skipped']) {
                $msg .= ' (' . count($result['skipped']) . ' pengaturan dilewati karena tidak ada di modem ini.)';
            }
            if ($ok) {
                \App\Services\GenieACSService::audit('acs_config_updated', $deviceId, array_intersect_key($params, array_flip($result['sent'])),
                    ['server_id' => $server->id, 'via' => 'web', 'result' => $result['state']]);
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => $ok, 'state' => $result['state'], 'message' => $msg, 'skipped' => $result['skipped']], $ok ? 200 : 422);
            }

            return back()->with($ok ? 'success' : 'error', $msg)
                ->with('acs_tab', $request->filled('wifi_ssid') || $request->filled('wifi_password') ? 'tab-wireless' : 'tab-wan');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SSID/Config Update Error for {$deviceId}: " . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Gagal mengirim konfigurasi: " . $e->getMessage()], 500);
            }
            return back()->with('error', "Gagal mengirim konfigurasi: " . $e->getMessage());
        }
    }

    /** Antrean perintah modem (dipantau halaman detail setelah Sinkron data). */
    public function deviceQueue(Request $request, string $deviceId)
    {
        $server = \App\Models\AcsServer::findOrFail($request->get('server_id'));
        $service = \App\Services\GenieACSService::forServer($server);
        try {
            $faults = $service->deviceFaults($deviceId);
            $tasks = collect($service->getTasks($deviceId)['data'] ?? [])->map(fn ($t) => [
                'id' => (string) ($t['_id'] ?? ''), 'name' => (string) ($t['name'] ?? ''),
                'fault' => $faults[(string) ($t['_id'] ?? '')] ?? null,
            ])->values();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json(['success' => true, 'tasks' => $tasks]);
    }

    /** Hapus satu perintah yang tertahan/gagal dari antrean modem. */
    public function deleteTask(Request $request, string $deviceId, string $taskId)
    {
        $server = \App\Models\AcsServer::findOrFail($request->get('server_id'));
        try {
            \App\Services\GenieACSService::forServer($server)->deleteTask($deviceId, $taskId);
            \App\Services\GenieACSService::audit('acs_task_deleted', $deviceId, [], ['server_id' => $server->id, 'task_id' => $taskId]);

            return back()->with('success', 'Perintah dihapus dari antrean modem.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus perintah: ' . $e->getMessage());
        }
    }

    /**
     * Reboot the device.
     */
    public function reboot(Request $request, string $deviceId)
    {
        $serverId = $request->get('server_id');
        $server = \App\Models\AcsServer::findOrFail($serverId);
        $service = \App\Services\GenieACSService::forServer($server);

        try {
            $service->reboot($deviceId);
            \App\Services\GenieACSService::audit('acs_rebooted', $deviceId, [], ['server_id' => $server->id, 'via' => 'web']);
            return back()->with('success', "Perintah reboot terkirim.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to reboot: " . $e->getMessage());
        }
    }

    /**
     * Refresh all device data from the ONT.
     */
    public function refreshDevice(Request $request, string $deviceId)
    {
        $serverId = $request->get('server_id');
        $server = \App\Models\AcsServer::findOrFail($serverId);
        $service = \App\Services\GenieACSService::forServer($server);

        try {
            // Nama objek TANPA titik di akhir: GenieACS 1.2 menolak "InternetGatewayDevice." dengan fault
            // "Invalid parameter path" (dulu setiap Sinkron data gagal dan perintahnya tertahan di antrean modem).
            $rootObject = 'InternetGatewayDevice';
            try {
                $devRes = $service->getDevice($deviceId);
                if (isset($devRes['data']['Device']) && !isset($devRes['data']['InternetGatewayDevice'])) {
                    $rootObject = 'Device';
                }
            } catch (\Throwable $e) {}

            // Bersihkan perintah sinkron rusak dari versi lama yang masih tertahan untuk modem ini
            $cleaned = $service->deleteBrokenRefreshTasks($deviceId);

            $state = $service->refreshDevice($deviceId, $rootObject);
            $msg = ($state === 'done'
                    ? 'Data modem sudah diperbarui — muat ulang halaman untuk melihatnya.'
                    : 'Perintah sinkron terkirim. Modem sedang membaca ulang datanya (bisa ±1 menit) — muat ulang halaman setelahnya.')
                . ($cleaned ? " ({$cleaned} perintah sinkron lama yang gagal dibersihkan.)" : '');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal sinkron: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Gagal sinkron: ' . $e->getMessage());
        }
    }

    /**
     * Update/edit tags for a device in GenieACS.
     */
    public function updateDeviceTags(Request $request, string $deviceId)
    {
        $serverId = $request->input('server_id');
        $server = \App\Models\AcsServer::findOrFail($serverId);

        $tagsInput = $request->input('tags', '');
        $newTags = is_array($tagsInput) 
            ? $tagsInput 
            : array_filter(array_map('trim', explode(',', $tagsInput)));

        $currentTagsInput = $request->input('current_tags', '');
        $currentTags = is_array($currentTagsInput) 
            ? $currentTagsInput 
            : array_filter(array_map('trim', explode(',', $currentTagsInput)));

        try {
            $service = \App\Services\GenieACSService::forServer($server);
            $service->syncTags($deviceId, $newTags, $currentTags);
            \App\Services\GenieACSService::audit('acs_tags_updated', $deviceId, [], ['tags' => array_values($newTags), 'via' => 'web']);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tags updated successfully.',
                    'tags'    => array_values($newTags)
                ]);
            }

            return back()->with('success', 'Tags updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update tags: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update tags: ' . $e->getMessage());
        }
    }
}
