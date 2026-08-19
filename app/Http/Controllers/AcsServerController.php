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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        \App\Models\AcsServer::create($validated);

        return redirect()->back()->with('success', 'ACS Server added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\AcsServer $acsServer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $acsServer->update($validated);

        return redirect()->back()->with('success', 'ACS Server updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
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
        $devices = [];
        $total = 0;
        $error = null;

        if ($selectedServer) {
            try {
                $service = \App\Services\GenieACSService::forServer($selectedServer);
                
                $query = [];
                
                // 1. Tag specific filter if provided
                if ($tagFilter) {
                    $cleanTag = trim($tagFilter);
                    $query['_tags'] = '/' . $cleanTag . '/i';
                }

                // 2. Global search bar (Tags, IDPEL, Serial, IP)
                if ($search) {
                    $cleanSearch = trim($search);
                    $cleanIdpel = explode('@', $cleanSearch)[0];
                    
                    // Search by _id, Tags, Serial Number, IP, or PPPoE Username (clean IDPEL)
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
                        // Both tag filter and search active
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
                    // VirtualParameters fallback (backward compatible if configured)
                    'VirtualParameters.ProductClass', 
                    'VirtualParameters.IP',
                    'VirtualParameters.wanip',
                    'VirtualParameters.getponrx',
                    'VirtualParameters.pppUsername',
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
                $error = "Failed to fetch devices from {$selectedServer->name}: " . $e->getMessage();
            }
        }

        // Manual Pagination
        $paginatedDevices = new \Illuminate\Pagination\LengthAwarePaginator(
            $devices, $total, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        return view('acs-servers.devices', compact('servers', 'selectedServer', 'paginatedDevices', 'error', 'search', 'tagFilter'));
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
            
            return view('acs-servers.device-details', compact('device', 'server', 'deviceId', 'service', 'tasks'));
        } catch (\Exception $e) {
            return view('acs-servers.device-details', [
                'device' => null,
                'server' => $server,
                'deviceId' => $deviceId,
                'service' => isset($service) ? $service : null,
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

        // Fetch device details to inspect schema and manufacturer
        $isTr181 = false;
        $isHuawei = false;
        try {
            $deviceResponse = $service->getDevice($deviceId);
            $deviceData = $deviceResponse['data'] ?? null;
            if ($deviceData) {
                // Detect TR-181 schema
                if (isset($deviceData['Device'])) {
                    $isTr181 = true;
                }
                
                // Detect manufacturer (e.g. Huawei, ZTE)
                $manufacturer = $deviceData['_deviceId']['_Manufacturer']
                    ?? $deviceData['DeviceID']['Manufacturer']['_value']
                    ?? $deviceData['Device']['DeviceInfo']['Manufacturer']['_value']
                    ?? '';
                if (stripos($manufacturer, 'Huawei') !== false) {
                    $isHuawei = true;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Could not pre-fetch device schema for {$deviceId}: " . $e->getMessage());
        }

        $params = [];

        if ($isTr181) {
            // TR-181 Schema Parameters
            if ($request->filled('ppp_username')) {
                $params['Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username'] = $request->ppp_username;
            }
            if ($request->filled('ppp_password')) {
                $params['Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password'] = $request->ppp_password;
            }
            if ($request->filled('wifi_ssid')) {
                $params["Device.WiFi.SSID.{$instance}.SSID"] = $request->wifi_ssid;
                $params["Device.WiFi.Radio.{$instance}.Enable"] = "1";
            }
            if ($request->filled('wifi_password')) {
                $params["Device.WiFi.AccessPoint.{$instance}.Security.KeyPassphrase"] = $request->wifi_password;
            }
        } else {
            // TR-069 Schema Parameters
            if ($request->filled('ppp_username')) {
                $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username'] = $request->ppp_username;
            }
            if ($request->filled('ppp_password')) {
                $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password'] = $request->ppp_password;
            }
            if ($request->filled('vlan_id')) {
                $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.X_HW_VLAN'] = $request->vlan_id;
            }
            if ($request->filled('wifi_ssid')) {
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.SSID"] = $request->wifi_ssid;
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.Enable"] = "1";
            }
            if ($request->filled('wifi_password')) {
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.PreSharedKey.1.PreSharedKey"] = $request->wifi_password;
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.KeyPassphrase"] = $request->wifi_password;
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.X_ZTE-COM_KeyPassphrase"] = $request->wifi_password;
            }
        }

        if (empty($params)) {
            return back()->with('error', "No changes to push.");
        }

        try {
            // Appended only for Huawei devices to avoid fault 9005 on ZTE/Fiberhome
            if ($isHuawei) {
                $params["InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig"] = "1";
            }

            // Push set parameters task (includes ?timeout=5000&connection_request automatically)
            try {
                $service->setParameters($deviceId, $params);
            } catch (\Exception $e) {
                // Task may still be queued in GenieACS even if we get a timeout/empty reply
                \Illuminate\Support\Facades\Log::warning("setParameters response issue (task likely queued): " . $e->getMessage());
            }

            // Queue a getParameterValues task for only the parent objects of updated parameters to refresh/summon them safely
            try {
                $refreshPaths = [];
                foreach ($params as $path => $value) {
                    if (stripos($path, 'SaveConfig') !== false) {
                        continue;
                    }
                    
                    // Split path and reconstruct parent path with trailing dot
                    $parts = explode('.', $path);
                    if (count($parts) > 1) {
                        array_pop($parts); // Remove leaf parameter name
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
                // Task may still be queued even on timeout
                \Illuminate\Support\Facades\Log::warning("getParameterValues response issue (task likely queued): " . $e->getMessage());
            }
            
            return back()->with('success', "Configuration task pushed. Check 'Pending Tasks' below. The ONT will apply changes when it connects.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SSID/Config Update Error for {$deviceId}: " . $e->getMessage());
            return back()->with('error', "Failed to push configuration: " . $e->getMessage());
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
            return back()->with('success', "Reboot command sent successfully.");
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
            // refreshObject with empty name refreshes EVERYTHING (includes ?timeout=5000&connection_request)
            $service->pushTask($deviceId, ['name' => 'refreshObject', 'objectName' => '']);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sync task pushed in background. Device is refreshing.'
                ]);
            }
            
            return back()->with('success', "Sync task pushed. Please wait a few seconds and refresh the page.");
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Failed to sync: " . $e->getMessage()
                ], 500);
            }
            return back()->with('error', "Failed to sync: " . $e->getMessage());
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
