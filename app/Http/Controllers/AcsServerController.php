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
        $perPage = 25;
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
                if ($search) {
                    // Search by _id or Serial Number using regex
                    $query['$or'] = [
                        ['_id' => '/' . $search . '/i'],
                        ['Device.DeviceInfo.SerialNumber' => '/' . $search . '/i'],
                        ['InternetGatewayDevice.DeviceInfo.SerialNumber' => '/' . $search . '/i']
                    ];
                }

                $projection = [
                    '_id', 
                    '_lastInform', 
                    'DeviceID.SerialNumber',
                    'DeviceID.ProductClass',
                    'DeviceID.Manufacturer',
                    '_deviceId._SerialNumber',
                    '_deviceId._ProductClass',
                    '_deviceId._Manufacturer',
                    'Device.DeviceInfo.SerialNumber',
                    'InternetGatewayDevice.DeviceInfo.SerialNumber',
                    'VirtualParameters.ProductClass', 
                    'Device.DeviceInfo.ProductClass',
                    'InternetGatewayDevice.DeviceInfo.ProductClass',
                    'Device.DeviceInfo.ModelName',
                    'InternetGatewayDevice.DeviceInfo.ModelName',
                    'InternetGatewayDevice.DeviceInfo.HardwareVersion',
                    'Device.DeviceInfo.HardwareVersion',
                    'InternetGatewayDevice.DeviceInfo.SoftwareVersion',
                    'Device.DeviceInfo.SoftwareVersion',
                    'VirtualParameters.IP',
                    'VirtualParameters.wanip',
                    'VirtualParameters.pppUsername',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Channel',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BeaconType',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.SSID',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.Enable',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.Channel',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.BeaconType',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2.KeyPassphrase',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.SSID',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.Enable',
                    'Device.WiFi.SSID.1.SSID',
                    'Device.WiFi.Radio.1.Enable',
                    'Device.WiFi.Radio.1.Channel',
                    'Device.WiFi.Radio.1.OperatingFrequencyBand',
                    'Device.WiFi.AccessPoint.1.Security.KeyPassphrase',
                    'Device.WiFi.SSID.2.SSID',
                    'Device.WiFi.Radio.2.Enable',
                    'Device.WiFi.Radio.2.Channel',
                    'Device.WiFi.Radio.2.OperatingFrequencyBand',
                    'Device.WiFi.AccessPoint.2.Security.KeyPassphrase',
                    'InternetGatewayDevice.LANDevice.1.Hosts.HostNumberOfEntries',
                    'Device.Hosts.HostNumberOfEntries',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.1.HostName',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.1.IPAddress',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.1.MACAddress',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.2.HostName',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.2.IPAddress',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.2.MACAddress',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.3.HostName',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.3.IPAddress',
                    'InternetGatewayDevice.LANDevice.1.Hosts.Host.3.MACAddress',
                    'Device.Hosts.Host.1.HostName',
                    'Device.Hosts.Host.1.IPAddress',
                    'Device.Hosts.Host.1.MACAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.X_HW_VLAN',
                    'Device.IP.Interface.1.IPv4Address.1.IPAddress',
                    'Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                    'Device.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress'
                ];
                
                $response = $service->getDevices($query, $projection, $skip, $perPage);
                $devices = $response['data'];
                $apiTotal = $response['total'];
                
                // If API total is missing or 0 but we have devices, use devices count as fallback
                if (($apiTotal === null || $apiTotal == 0) && count($devices) > 0) {
                    $total = count($devices) < $perPage ? count($devices) : $skip + count($devices) + 1;
                } else {
                    $total = $apiTotal ?? 0;
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

        return view('acs-servers.devices', compact('servers', 'selectedServer', 'paginatedDevices', 'error', 'search'));
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

        $params = [];
        
        // PPPoE Settings (Instance doesn't usually apply here)
        if ($request->filled('ppp_username')) {
            $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username'] = $request->ppp_username;
            $params['Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username'] = $request->ppp_username;
        }

        if ($request->filled('ppp_password')) {
            $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password'] = $request->ppp_password;
            $params['Device.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password'] = $request->ppp_password;
        }

        if ($request->filled('vlan_id')) {
            $params['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.X_HW_VLAN'] = $request->vlan_id;
        }

        // WLAN Settings (Using instance)
        if ($request->filled('wifi_ssid')) {
            $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.SSID"] = $request->wifi_ssid;
            $params["Device.WiFi.SSID.{$instance}.SSID"] = $request->wifi_ssid;
            // Force enable when updating
            $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.Enable"] = "1";
            $params["Device.WiFi.Radio.{$instance}.Enable"] = "1";
        }

        if ($request->filled('wifi_password')) {
            $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.PreSharedKey.1.PreSharedKey"] = $request->wifi_password;
            $params["Device.WiFi.AccessPoint.{$instance}.Security.KeyPassphrase"] = $request->wifi_password;
            $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.KeyPassphrase"] = $request->wifi_password;
            $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.X_ZTE-COM_KeyPassphrase"] = $request->wifi_password;
        }

        if (empty($params)) {
            return back()->with('error', "No changes to push.");
        }

        try {
            // Huawei specific save config (important for HG8546M)
            $params["InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig"] = "1";

            // Push set parameters task
            $service->setParameters($deviceId, $params);
            
            // Try to nudge the device to inform immediately
            try {
                $service->pushTask($deviceId, ['name' => 'connectionRequest']);
            } catch (\Exception $e) {
                // Ignore connection request failures
            }
            
            return back()->with('success', "Configuration task pushed. Check 'Pending Tasks' below. The ONT will apply changes when it connects.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SSID Update Error for {$deviceId}: " . $e->getMessage());
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
            // refreshObject with empty name refreshes EVERYTHING
            $service->pushTask($deviceId, ['name' => 'refreshObject', 'objectName' => '']);
            // Also push a connection request
            $service->pushTask($deviceId, ['name' => 'connectionRequest']);
            
            return back()->with('success', "Sync task pushed. Please wait a few seconds and refresh the page.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to sync: " . $e->getMessage());
        }
    }
}
