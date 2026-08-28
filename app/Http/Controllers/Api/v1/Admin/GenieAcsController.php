<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcsServer;
use App\Services\GenieACSService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GenieAcsController extends Controller
{
    /**
     * Get list of configured ACS Servers with quick status.
     */
    public function servers(): JsonResponse
    {
        $servers = AcsServer::all()->map(function ($server) {
            $deviceCount = 0;
            $isOnline = false;

            if ($server->is_active) {
                try {
                    $service = GenieACSService::forServer($server);
                    $response = $service->getDevices([], ['_id'], 0, 1);
                    $isOnline = true;
                    $deviceCount = $response['total'] ?? 0;
                } catch (\Exception $e) {
                    $isOnline = false;
                }
            }

            return [
                'id' => $server->id,
                'name' => $server->name,
                'url' => $server->url,
                'description' => $server->description,
                'is_active' => (bool) $server->is_active,
                'is_reachable' => $isOnline,
                'device_count' => $deviceCount,
                'created_at' => $server->created_at?->toIso8601String(),
                'updated_at' => $server->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'servers' => $servers,
        ]);
    }

    /**
     * Create a new ACS Server.
     */
    public function storeServer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $server = AcsServer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Server GenieACS berhasil ditambahkan',
            'server' => $server,
        ], 201);
    }

    /**
     * Update an ACS Server.
     */
    public function updateServer(Request $request, int $id): JsonResponse
    {
        $server = AcsServer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $server->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Server GenieACS berhasil diperbarui',
            'server' => $server,
        ]);
    }

    /**
     * Delete an ACS Server.
     */
    public function destroyServer(int $id): JsonResponse
    {
        $server = AcsServer::findOrFail($id);
        $server->delete();

        return response()->json([
            'success' => true,
            'message' => 'Server GenieACS berhasil dihapus',
        ]);
    }

    /**
     * Get list of devices with filter, search, and pagination.
     */
    public function devices(Request $request): JsonResponse
    {
        $serverId = $request->query('server_id');
        $search = $request->query('search');
        $tagFilter = $request->query('tag');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));
        $skip = ($page - 1) * $perPage;

        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada Server GenieACS yang aktif.',
                'devices' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
            ]);
        }

        try {
            $service = GenieACSService::forServer($server);
            $query = [];

            // 1. Tag filter
            if (!empty($tagFilter)) {
                $cleanTag = trim($tagFilter);
                $query['_tags'] = '/' . $cleanTag . '/i';
            }

            // 2. Search query
            if (!empty($search)) {
                $cleanSearch = trim($search);
                $cleanIdpel = explode('@', $cleanSearch)[0];

                $orConditions = [
                    ['_id' => '/' . $cleanSearch . '/i'],
                    ['_tags' => $cleanSearch],
                    ['_tags' => '/' . $cleanSearch . '/i'],
                    ['_tags' => '/' . $cleanIdpel . '/i'],
                    ['Tags' => '/' . $cleanSearch . '/i'],
                    ['VirtualParameters.tag' => '/' . $cleanSearch . '/i'],
                    ['VirtualParameters.tags' => '/' . $cleanSearch . '/i'],
                    ['_deviceId._SerialNumber' => '/' . $cleanSearch . '/i'],
                    ['DeviceID.SerialNumber' => '/' . $cleanSearch . '/i'],
                    ['Device.DeviceInfo.SerialNumber' => '/' . $cleanSearch . '/i'],
                    ['InternetGatewayDevice.DeviceInfo.SerialNumber' => '/' . $cleanSearch . '/i'],
                    ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username' => '/' . $cleanIdpel . '/i'],
                    ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username' => '/' . $cleanIdpel . '/i'],
                    ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username' => '/' . $cleanIdpel . '/i'],
                    ['Device.PPP.Interface.1.Username' => '/' . $cleanIdpel . '/i'],
                    ['Device.PPP.Interface.2.Username' => '/' . $cleanIdpel . '/i'],
                    ['VirtualParameters.pppUsername' => '/' . $cleanIdpel . '/i'],
                    ['VirtualParameters.IP' => '/' . $cleanSearch . '/i'],
                    ['VirtualParameters.wanip' => '/' . $cleanSearch . '/i'],
                    ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress' => '/' . $cleanSearch . '/i'],
                    ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress' => '/' . $cleanSearch . '/i'],
                    ['Device.IP.Interface.1.IPv4Address.1.IPAddress' => '/' . $cleanSearch . '/i'],
                ];

                if (isset($query['_tags'])) {
                    $query = [
                        '$and' => [
                            ['_tags' => $query['_tags']],
                            ['$or' => $orConditions]
                        ]
                    ];
                } else {
                    $query['$or'] = $orConditions;
                }
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
                'Device.DeviceInfo.SerialNumber',
                'InternetGatewayDevice.DeviceInfo.SerialNumber',
                'Device.DeviceInfo.ProductClass',
                'InternetGatewayDevice.DeviceInfo.ProductClass',
                'Device.DeviceInfo.ModelName',
                'InternetGatewayDevice.DeviceInfo.ModelName',
                'Device.IP.Interface.1.IPv4Address.1.IPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
                'Device.PPP.Interface.1.Username',
                'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RXPower',
                'InternetGatewayDevice.WANDevice.1.X_HW_OpticalInfo.RxPower',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_ZTE-COM_WANPONInterfaceConfig.RXPower',
                'InternetGatewayDevice.WANDevice.1.X_ZTE-COM_Optical.RxPower',
                'InternetGatewayDevice.X_ALU_OntOpticalParam.RXPower',
                'Device.Optical.Interface.1.OpticalPowerRx',
                'VirtualParameters.ProductClass',
                'VirtualParameters.IP',
                'VirtualParameters.wanip',
                'VirtualParameters.getponrx',
                'VirtualParameters.pppUsername',
            ];

            $response = $service->getDevices($query, $projection, $skip, $perPage);
            $rawDevices = $response['data'] ?? [];
            $total = $response['total'] ?? count($rawDevices);

            $normalizedDevices = array_map(function ($device) {
                return $this->normalizeDeviceListItem($device);
            }, $rawDevices);

            return response()->json([
                'success' => true,
                'server' => [
                    'id' => $server->id,
                    'name' => $server->name,
                ],
                'devices' => $normalizedDevices,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / max(1, $perPage)),
            ]);

        } catch (Exception $e) {
            Log::error("GenieAcsController@devices error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat perangkat dari ACS: ' . $e->getMessage(),
                'devices' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
            ], 500);
        }
    }

    /**
     * Get detailed telemetry and parameters of a specific device.
     */
    public function showDevice(Request $request, string $deviceId): JsonResponse
    {
        $serverId = $request->query('server_id');
        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Server GenieACS tidak ditemukan atau tidak aktif.',
            ], 404);
        }

        try {
            $service = GenieACSService::forServer($server);
            $deviceResponse = $service->getDevice($deviceId);
            $device = $deviceResponse['data'] ?? null;

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat tidak ditemukan di server GenieACS.',
                ], 404);
            }

            // Fetch tasks
            $tasks = [];
            try {
                $tasksResponse = $service->getTasks($deviceId);
                $tasks = $tasksResponse['data'] ?? [];
            } catch (\Exception $e) {
                Log::warning("Could not fetch tasks for {$deviceId}: " . $e->getMessage());
            }

            $detail = $this->normalizeDeviceDetail($device, $tasks, $server);

            return response()->json([
                'success' => true,
                'device' => $detail,
            ]);

        } catch (Exception $e) {
            Log::error("GenieAcsController@showDevice error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail perangkat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push Reboot command to ONT.
     */
    public function rebootDevice(Request $request, string $deviceId): JsonResponse
    {
        $serverId = $request->input('server_id');
        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server ACS tidak ditemukan.'], 404);
        }

        try {
            $service = GenieACSService::forServer($server);
            $service->reboot($deviceId);

            return response()->json([
                'success' => true,
                'message' => 'Perintah Reboot berhasil dikirim ke perangkat ONT.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah Reboot: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push Refresh/Sync all data command to ONT.
     */
    public function refreshDevice(Request $request, string $deviceId): JsonResponse
    {
        $serverId = $request->input('server_id');
        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server ACS tidak ditemukan.'], 404);
        }

        try {
            $service = GenieACSService::forServer($server);
            $service->pushTask($deviceId, ['name' => 'refreshObject', 'objectName' => '']);

            return response()->json([
                'success' => true,
                'message' => 'Tugas Sinkronisasi / Refresh berhasil didorong. ONT akan memperbarui telemetri saat terhubung.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memicu refresh data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push Factory Reset command to ONT.
     */
    public function factoryResetDevice(Request $request, string $deviceId): JsonResponse
    {
        $serverId = $request->input('server_id');
        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server ACS tidak ditemukan.'], 404);
        }

        try {
            $service = GenieACSService::forServer($server);
            $service->factoryReset($deviceId);

            return response()->json([
                'success' => true,
                'message' => 'Perintah Factory Reset berhasil dikirim ke ONT.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan Factory Reset: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update WiFi and PPPoE configuration on ONT.
     */
    public function updateConfig(Request $request, string $deviceId): JsonResponse
    {
        $serverId = $request->input('server_id');
        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server ACS tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'wifi_ssid' => 'nullable|string|max:64',
            'wifi_password' => 'nullable|string|min:8|max:64',
            'ppp_username' => 'nullable|string|max:64',
            'ppp_password' => 'nullable|string|max:64',
            'vlan_id' => 'nullable|string|max:10',
            'instance' => 'nullable|integer|min:1|max:8',
        ]);

        $instance = $validated['instance'] ?? 1;
        $service = GenieACSService::forServer($server);

        // Detect schema & vendor
        $isTr181 = false;
        $isHuawei = false;
        try {
            $devRes = $service->getDevice($deviceId);
            $devData = $devRes['data'] ?? null;
            if ($devData) {
                if (isset($devData['Device'])) {
                    $isTr181 = true;
                }
                $manufacturer = $devData['_deviceId']['_Manufacturer']
                    ?? $devData['DeviceID']['Manufacturer']['_value']
                    ?? $devData['Device']['DeviceInfo']['Manufacturer']['_value']
                    ?? '';
                if (stripos($manufacturer, 'Huawei') !== false) {
                    $isHuawei = true;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Could not prefetch device schema: " . $e->getMessage());
        }

        $params = [];
        if ($isTr181) {
            if (!empty($validated['ppp_username'])) {
                $params['Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username'] = $validated['ppp_username'];
            }
            if (!empty($validated['ppp_password'])) {
                $params['Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password'] = $validated['ppp_password'];
            }
            if (!empty($validated['wifi_ssid'])) {
                $params["Device.WiFi.SSID.{$instance}.SSID"] = $validated['wifi_ssid'];
                $params["Device.WiFi.Radio.{$instance}.Enable"] = "1";
            }
            if (!empty($validated['wifi_password'])) {
                $params["Device.WiFi.AccessPoint.{$instance}.Security.KeyPassphrase"] = $validated['wifi_password'];
            }
        } else {
            if (!empty($validated['ppp_username'])) {
                $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username'] = $validated['ppp_username'];
            }
            if (!empty($validated['ppp_password'])) {
                $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password'] = $validated['ppp_password'];
            }
            if (!empty($validated['vlan_id'])) {
                $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.X_HW_VLAN'] = $validated['vlan_id'];
            }
            if (!empty($validated['wifi_ssid'])) {
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.SSID"] = $validated['wifi_ssid'];
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.Enable"] = "1";
            }
            if (!empty($validated['wifi_password'])) {
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.PreSharedKey.1.PreSharedKey"] = $validated['wifi_password'];
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.KeyPassphrase"] = $validated['wifi_password'];
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.X_ZTE-COM_KeyPassphrase"] = $validated['wifi_password'];
            }
        }

        if (empty($params)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada perubahan konfigurasi yang dikirim.'], 400);
        }

        try {
            if ($isHuawei) {
                $params["InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig"] = "1";
            }

            try {
                $service->setParameters($deviceId, $params);
            } catch (\Exception $e) {
                Log::warning("setParameters response note (task likely queued): " . $e->getMessage());
            }

            // Also queue getParameterValues to refresh updated parameters
            try {
                $refreshPaths = [];
                foreach ($params as $path => $value) {
                    if (stripos($path, 'SaveConfig') !== false) {
                        continue;
                    }
                    $parts = explode('.', $path);
                    if (count($parts) > 1) {
                        array_pop($parts);
                        $parentPath = implode('.', $parts) . '.';
                        $refreshPaths[$parentPath] = true;
                    }
                }
                if (!empty($refreshPaths)) {
                    $service->pushTask($deviceId, [
                        'name' => 'getParameterValues',
                        'parameterNames' => array_keys($refreshPaths)
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("getParameterValues task notice: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi berhasil didorong. ONT akan menerapkan perubahan saat terkoneksi ke ACS.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendorong konfigurasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update tags for a device.
     */
    public function updateTags(Request $request, string $deviceId): JsonResponse
    {
        $serverId = $request->input('server_id');
        $server = $serverId ? AcsServer::find($serverId) : AcsServer::where('is_active', true)->first();

        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server ACS tidak ditemukan.'], 404);
        }

        $newTags = $request->input('tags', []);
        if (is_string($newTags)) {
            $newTags = array_filter(array_map('trim', explode(',', $newTags)));
        }

        $currentTags = $request->input('current_tags', []);
        if (is_string($currentTags)) {
            $currentTags = array_filter(array_map('trim', explode(',', $currentTags)));
        }

        try {
            $service = GenieACSService::forServer($server);
            $service->syncTags($deviceId, $newTags, $currentTags);

            return response()->json([
                'success' => true,
                'message' => 'Tag perangkat berhasil diperbarui.',
                'tags' => array_values(array_unique($newTags)),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui tag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Normalize device item for list view.
     */
    protected function normalizeDeviceListItem(array $device): array
    {
        $id = $device['_id'] ?? '';
        $tags = $device['_tags'] ?? $device['Tags'] ?? [];
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $lastInform = $device['_lastInform'] ?? null;
        $isOnline = false;
        if ($lastInform) {
            $lastInformTime = strtotime($lastInform);
            $isOnline = ($lastInformTime !== false && (time() - $lastInformTime) <= 300); // 5 minutes threshold
        }

        $serialNumber = $this->extractParamValue($device, [
            '_deviceId._SerialNumber',
            'DeviceID.SerialNumber',
            'Device.DeviceInfo.SerialNumber',
            'InternetGatewayDevice.DeviceInfo.SerialNumber',
        ]) ?: $id;

        $manufacturer = $this->extractParamValue($device, [
            '_deviceId._Manufacturer',
            'DeviceID.Manufacturer',
            'Device.DeviceInfo.Manufacturer',
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
        ]) ?: 'Generic';

        $productClass = $this->extractParamValue($device, [
            '_deviceId._ProductClass',
            'DeviceID.ProductClass',
            'Device.DeviceInfo.ProductClass',
            'InternetGatewayDevice.DeviceInfo.ProductClass',
            'Device.DeviceInfo.ModelName',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'VirtualParameters.ProductClass',
        ]) ?: 'ONT';

        $ip = $this->extractParamValue($device, [
            'Device.IP.Interface.1.IPv4Address.1.IPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
            'VirtualParameters.IP',
            'VirtualParameters.wanip',
        ]) ?: '-';

        $pppUsername = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
            'Device.PPP.Interface.1.Username',
            'VirtualParameters.pppUsername',
        ]) ?: '-';

        $rxPower = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_HW_OpticalInfo.RxPower',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_ZTE-COM_WANPONInterfaceConfig.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_ZTE-COM_Optical.RxPower',
            'InternetGatewayDevice.X_ALU_OntOpticalParam.RXPower',
            'Device.Optical.Interface.1.OpticalPowerRx',
            'VirtualParameters.getponrx',
        ]);

        $rxNum = $rxPower !== null ? (float) $rxPower : null;
        if ($rxNum !== null && abs($rxNum) > 100) {
            $rxNum = round($rxNum / 100, 2);
        }

        return [
            'id' => $id,
            'serial_number' => $serialNumber,
            'manufacturer' => $manufacturer,
            'product_class' => $productClass,
            'ip' => $ip,
            'ppp_username' => $pppUsername,
            'rx_power' => $rxNum,
            'is_online' => $isOnline,
            'last_inform' => $lastInform,
            'tags' => array_values($tags),
        ];
    }

    /**
     * Helper: Normalize full device details.
     */
    protected function normalizeDeviceDetail(array $device, array $tasks, AcsServer $server): array
    {
        $id = $device['_id'] ?? '';
        $tags = $device['_tags'] ?? $device['Tags'] ?? [];
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $lastInform = $device['_lastInform'] ?? null;
        $isOnline = false;
        if ($lastInform) {
            $lastInformTime = strtotime($lastInform);
            $isOnline = ($lastInformTime !== false && (time() - $lastInformTime) <= 300);
        }

        // Hardware & Device Info
        $serialNumber = $this->extractParamValue($device, [
            '_deviceId._SerialNumber',
            'DeviceID.SerialNumber',
            'Device.DeviceInfo.SerialNumber',
            'InternetGatewayDevice.DeviceInfo.SerialNumber',
        ]) ?: $id;

        $manufacturer = $this->extractParamValue($device, [
            '_deviceId._Manufacturer',
            'DeviceID.Manufacturer',
            'Device.DeviceInfo.Manufacturer',
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
        ]) ?: 'Generic';

        $model = $this->extractParamValue($device, [
            '_deviceId._ProductClass',
            'DeviceID.ProductClass',
            'Device.DeviceInfo.ModelName',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'Device.DeviceInfo.ProductClass',
            'InternetGatewayDevice.DeviceInfo.ProductClass',
            'VirtualParameters.ProductClass',
        ]) ?: 'ONT';

        $hardwareVersion = $this->extractParamValue($device, [
            'Device.DeviceInfo.HardwareVersion',
            'InternetGatewayDevice.DeviceInfo.HardwareVersion',
        ]) ?: '-';

        $softwareVersion = $this->extractParamValue($device, [
            'Device.DeviceInfo.SoftwareVersion',
            'InternetGatewayDevice.DeviceInfo.SoftwareVersion',
        ]) ?: '-';

        $uptimeSeconds = (int) ($this->extractParamValue($device, [
            'Device.DeviceInfo.UpTime',
            'InternetGatewayDevice.DeviceInfo.UpTime',
        ]) ?: 0);

        // Optical Signal
        $rxPower = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_HW_OpticalInfo.RxPower',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_ZTE-COM_WANPONInterfaceConfig.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_ZTE-COM_Optical.RxPower',
            'InternetGatewayDevice.X_ALU_OntOpticalParam.RXPower',
            'Device.Optical.Interface.1.OpticalPowerRx',
            'VirtualParameters.getponrx',
        ]);

        $txPower = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.X_HW_OpticalInfo.TxPower',
            'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.TXPower',
            'Device.Optical.Interface.1.OpticalPowerTx',
        ]);

        $opticalHealth = 'unknown';
        $rxNum = $rxPower !== null ? (float) $rxPower : null;
        if ($rxNum !== null && abs($rxNum) > 100) {
            $rxNum = round($rxNum / 100, 2);
        }

        $txNum = $txPower !== null ? (float) $txPower : null;
        if ($txNum !== null && abs($txNum) > 100) {
            $txNum = round($txNum / 100, 2);
        }

        if ($rxNum !== null) {
            if ($rxNum >= -24.0) {
                $opticalHealth = 'good';
            } elseif ($rxNum >= -27.0) {
                $opticalHealth = 'warning';
            } else {
                $opticalHealth = 'critical';
            }
        }

        // WAN Info
        $pppUsername = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
            'Device.PPP.Interface.1.Username',
            'VirtualParameters.pppUsername',
        ]) ?: '-';

        $wanIp = $this->extractParamValue($device, [
            'Device.IP.Interface.1.IPv4Address.1.IPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
            'VirtualParameters.IP',
            'VirtualParameters.wanip',
        ]) ?: '-';

        $wanStatus = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus',
            'Device.PPP.Interface.1.ConnectionStatus',
        ]) ?: ($isOnline ? 'Connected' : 'Disconnected');

        $macAddress = $this->extractParamValue($device, [
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.MACAddress',
            'Device.PPP.Interface.1.MACAddress',
            'InternetGatewayDevice.LANDevice.1.LANEthernetInterfaceConfig.1.MACAddress',
        ]) ?: '-';

        // WiFi Info (Instance 1 & 2)
        $wifiSsid = $this->extractParamValue($device, [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
            'Device.WiFi.SSID.1.SSID',
        ]) ?: '-';

        $wifiEnabled = $this->extractParamValue($device, [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable',
            'Device.WiFi.Radio.1.Enable',
        ]) == '1' || $this->extractParamValue($device, ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable']) === true;

        $wifiSecurity = $this->extractParamValue($device, [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BeaconType',
            'Device.WiFi.AccessPoint.1.Security.ModeEnabled',
        ]) ?: 'WPA2-PSK';

        $wifiChannel = $this->extractParamValue($device, [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Channel',
            'Device.WiFi.Radio.1.Channel',
        ]) ?: 'Auto';

        // Connected Hosts / Clients
        $clients = $this->extractConnectedClients($device);

        // Normalize Tasks
        $normalizedTasks = array_map(function ($task) {
            return [
                'id' => $task['_id'] ?? '',
                'name' => $task['name'] ?? 'Task',
                'status' => $task['status'] ?? 'pending',
                'timestamp' => $task['timestamp'] ?? null,
            ];
        }, $tasks);

        return [
            'id' => $id,
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'url' => $server->url,
            ],
            'is_online' => $isOnline,
            'last_inform' => $lastInform,
            'tags' => array_values($tags),
            'info' => [
                'serial_number' => $serialNumber,
                'manufacturer' => $manufacturer,
                'model' => $model,
                'hardware_version' => $hardwareVersion,
                'software_version' => $softwareVersion,
                'uptime_seconds' => $uptimeSeconds,
            ],
            'optical' => [
                'rx_power' => $rxNum,
                'tx_power' => $txNum,
                'health' => $opticalHealth,
            ],
            'wan' => [
                'ppp_username' => $pppUsername,
                'ip_address' => $wanIp,
                'status' => $wanStatus,
                'mac_address' => $macAddress,
            ],
            'wifi' => [
                'ssid' => $wifiSsid,
                'is_enabled' => $wifiEnabled,
                'security' => $wifiSecurity,
                'channel' => $wifiChannel,
            ],
            'clients' => $clients,
            'tasks' => $normalizedTasks,
        ];
    }

    /**
     * Helper: Extract connected clients / hosts from TR-069 or TR-181 tree.
     */
    protected function extractConnectedClients(array $device): array
    {
        $clients = [];

        // TR-069: InternetGatewayDevice.LANDevice.1.Hosts.Host.*
        $hostsObj = data_get($device, 'InternetGatewayDevice.LANDevice.1.Hosts.Host', []);
        if (is_array($hostsObj)) {
            foreach ($hostsObj as $key => $host) {
                if (!is_array($host) || str_starts_with($key, '_')) continue;
                $hostname = data_get($host, 'HostName._value') ?? data_get($host, 'HostName') ?? '-';
                $ip = data_get($host, 'IPAddress._value') ?? data_get($host, 'IPAddress') ?? '-';
                $mac = data_get($host, 'MACAddress._value') ?? data_get($host, 'MACAddress') ?? '-';
                $active = data_get($host, 'Active._value') ?? data_get($host, 'Active') ?? true;

                if ($ip !== '-' || $mac !== '-') {
                    $clients[] = [
                        'hostname' => $hostname,
                        'ip' => $ip,
                        'mac' => $mac,
                        'is_active' => (bool) $active,
                    ];
                }
            }
        }

        // TR-181: Device.Hosts.Host.*
        if (empty($clients)) {
            $hostsObj181 = data_get($device, 'Device.Hosts.Host', []);
            if (is_array($hostsObj181)) {
                foreach ($hostsObj181 as $key => $host) {
                    if (!is_array($host) || str_starts_with($key, '_')) continue;
                    $hostname = data_get($host, 'HostName._value') ?? data_get($host, 'HostName') ?? '-';
                    $ip = data_get($host, 'IPAddress._value') ?? data_get($host, 'IPAddress') ?? '-';
                    $mac = data_get($host, 'PhysAddress._value') ?? data_get($host, 'PhysAddress') ?? '-';
                    $active = data_get($host, 'Active._value') ?? data_get($host, 'Active') ?? true;

                    if ($ip !== '-' || $mac !== '-') {
                        $clients[] = [
                            'hostname' => $hostname,
                            'ip' => $ip,
                            'mac' => $mac,
                            'is_active' => (bool) $active,
                        ];
                    }
                }
            }
        }

        return $clients;
    }

    /**
     * Helper: Extract parameter value from nested array with multiple candidate paths.
     */
    protected function extractParamValue(array $device, array $paths)
    {
        foreach ($paths as $path) {
            $val = data_get($device, $path);
            if ($val !== null) {
                if (is_array($val) && isset($val['_value'])) {
                    return $val['_value'];
                }
                if (!is_array($val)) {
                    return $val;
                }
            }
        }
        return null;
    }
}
