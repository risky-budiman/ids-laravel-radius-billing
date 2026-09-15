<?php

namespace App\Services;

use App\Models\AcsServer;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieACSService
{
    protected ?string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Set the target ACS server model.
     */
    public static function forServer(AcsServer $server): self
    {
        return new self($server->url);
    }

    /**
     * Set the target URL directly.
     */
    public static function forUrl(string $url): self
    {
        return new self($url);
    }

    /**
     * Get devices with optional query and projection.
     * Query can be like: ["_id" => "OUI-Product-SN"]
     * Projection can be like: ["_id", "_lastInform", "Device.DeviceInfo.ProductClass"]
     */
    public function getDevices(array $query = [], array $projection = [], int $skip = 0, int $limit = 25)
    {
        $encodedQuery = json_encode(empty($query) ? (object)[] : $query);
        $params = [
            'query' => $encodedQuery,
            'skip' => $skip,
            'limit' => $limit,
            'total' => 'true'
        ];
        
        if (!empty($projection)) {
            $params['projection'] = implode(',', $projection);
        }
        
        $queryString = http_build_query($params);
        return $this->request('GET', "devices?{$queryString}");
    }

    /**
     * Get a specific device details.
     */
    public function getDevice(string $deviceId)
    {
        // Use query instead of direct ID path to avoid 405 errors on some GenieACS setups
        $query = ['_id' => $deviceId];
        $params = [
            'query' => json_encode($query),
        ];
        $queryString = http_build_query($params);
        $response = $this->request('GET', "devices?{$queryString}");
        
        // Return the first device from data array if found
        if (!empty($response['data']) && is_array($response['data'])) {
            $response['data'] = $response['data'][0];
        } else {
            $response['data'] = null;
        }
        
        return $response;
    }

    /**
     * Get pending tasks for a device.
     */
    public function getTasks(string $deviceId)
    {
        $query = ['device' => $deviceId];
        $queryString = http_build_query(['query' => json_encode($query)]);
        return $this->request('GET', "tasks?{$queryString}");
    }

    /**
     * Set TR-069 parameters on a device.
     */
    public function setParameters(string $deviceId, array $parameters)
    {
        $values = [];
        foreach ($parameters as $name => $value) {
            $values[] = [$name, $value];
        }

        return $this->pushTask($deviceId, [
            'name' => 'setParameterValues',
            'parameterValues' => $values
        ]);
    }

    /**
     * Extract all detected WAN connections from TR-069 or TR-181 device data tree.
     */
    public function extractWanConnections(array $dev): array
    {
        $connections = [];
        $mfg = $dev['_deviceId']['_Manufacturer'] ?? $dev['DeviceID']['Manufacturer']['_value'] ?? '';
        $productClass = $dev['_deviceId']['_ProductClass'] ?? $dev['DeviceID']['ProductClass']['_value'] ?? '';
        $devId = $dev['_id'] ?? '';
        
        $isHuawei = stripos($mfg, 'Huawei') !== false 
            || stripos($productClass, 'HG8') !== false 
            || stripos($productClass, 'EG8') !== false 
            || stripos($devId, '00259E') !== false;

        // 1. TR-069 Data Model: InternetGatewayDevice.WANDevice.*
        $wanDevices = $dev['InternetGatewayDevice']['WANDevice'] ?? [];
        foreach ($wanDevices as $wIndex => $wVal) {
            if (!is_numeric($wIndex) || !is_array($wVal)) continue;
            $connDevices = $wVal['WANConnectionDevice'] ?? [];
            foreach ($connDevices as $cIndex => $cVal) {
                if (!is_numeric($cIndex) || !is_array($cVal)) continue;
                $wanConnPath = "InternetGatewayDevice.WANDevice.{$wIndex}.WANConnectionDevice.{$cIndex}";
                
                // PPP Connections
                $pppConns = $cVal['WANPPPConnection'] ?? [];
                foreach ($pppConns as $pIndex => $pVal) {
                    if (!is_numeric($pIndex) || !is_array($pVal)) continue;
                    $path = "{$wanConnPath}.WANPPPConnection.{$pIndex}";
                    
                    // Discover VLAN parameter path & current value
                    $vlanPath = null;
                    $vlanVal = null;
                    if (isset($pVal['X_HW_VLAN']['_value'])) {
                        $vlanPath = "{$path}.X_HW_VLAN";
                        $vlanVal = $pVal['X_HW_VLAN']['_value'];
                    } elseif (isset($pVal['X_ZTE-COM_VLANID']['_value'])) {
                        $vlanPath = "{$path}.X_ZTE-COM_VLANID";
                        $vlanVal = $pVal['X_ZTE-COM_VLANID']['_value'];
                    } elseif (isset($pVal['VLANIDMark']['_value'])) {
                        $vlanPath = "{$path}.VLANIDMark";
                        $vlanVal = $pVal['VLANIDMark']['_value'];
                    } elseif (isset($cVal['WANEthernetLinkConfig']['X_ZTE-COM_VLANID']['_value'])) {
                        $vlanPath = "{$wanConnPath}.WANEthernetLinkConfig.X_ZTE-COM_VLANID";
                        $vlanVal = $cVal['WANEthernetLinkConfig']['X_ZTE-COM_VLANID']['_value'];
                    } elseif (isset($cVal['WANEthernetLinkConfig']['VLANIDMark']['_value'])) {
                        $vlanPath = "{$wanConnPath}.WANEthernetLinkConfig.VLANIDMark";
                        $vlanVal = $cVal['WANEthernetLinkConfig']['VLANIDMark']['_value'];
                    } elseif (isset($pVal['X_CT-COM_VLANID']['_value'])) {
                        $vlanPath = "{$path}.X_CT-COM_VLANID";
                        $vlanVal = $pVal['X_CT-COM_VLANID']['_value'];
                    } elseif (isset($pVal['X_FH_VLAN']['_value'])) {
                        $vlanPath = "{$path}.X_FH_VLAN";
                        $vlanVal = $pVal['X_FH_VLAN']['_value'];
                    }

                    // Discover ServiceList parameter path & current value
                    $servPath = null;
                    $servVal = null;
                    if (isset($pVal['X_HW_SERVICELIST']['_value'])) {
                        $servPath = "{$path}.X_HW_SERVICELIST";
                        $servVal = $pVal['X_HW_SERVICELIST']['_value'];
                    } elseif (isset($pVal['ServiceList']['_value'])) {
                        $servPath = "{$path}.ServiceList";
                        $servVal = $pVal['ServiceList']['_value'];
                    } elseif (isset($pVal['X_ZTE-COM_ServiceList']['_value'])) {
                        $servPath = "{$path}.X_ZTE-COM_ServiceList";
                        $servVal = $pVal['X_ZTE-COM_ServiceList']['_value'];
                    }

                    // LAN & SSID Interface Bindings
                    $lanBind = ['lan1' => false, 'lan2' => false, 'lan3' => false, 'lan4' => false];
                    $ssidBind = ['ssid1' => false, 'ssid2' => false, 'ssid3' => false, 'ssid4' => false];

                    if (isset($pVal['X_HW_LANBIND']) && is_array($pVal['X_HW_LANBIND'])) {
                        $hw = $pVal['X_HW_LANBIND'];
                        for ($i = 1; $i <= 4; $i++) {
                            $lanBind["lan{$i}"] = !empty($hw["Lan{$i}Enable"]['_value']);
                            $ssidBind["ssid{$i}"] = !empty($hw["SSID{$i}Enable"]['_value']);
                        }
                    } elseif (isset($pVal['X_ZTE-COM_LANBinding']['_value'])) {
                        $zteVal = strtoupper((string) $pVal['X_ZTE-COM_LANBinding']['_value']);
                        for ($i = 1; $i <= 4; $i++) {
                            $lanBind["lan{$i}"] = str_contains($zteVal, "LAN{$i}");
                            $ssidBind["ssid{$i}"] = str_contains($zteVal, "SSID{$i}") || str_contains($zteVal, "WLAN{$i}");
                        }
                    } elseif (isset($pVal['X_CT-COM_LANBinding']['_value'])) {
                        $ctVal = strtoupper((string) $pVal['X_CT-COM_LANBinding']['_value']);
                        for ($i = 1; $i <= 4; $i++) {
                            $lanBind["lan{$i}"] = str_contains($ctVal, "LAN{$i}");
                            $ssidBind["ssid{$i}"] = str_contains($ctVal, "SSID{$i}") || str_contains($ctVal, "WLAN{$i}");
                        }
                    }

                    $isEnabled = isset($pVal['Enable']['_value']) 
                        ? ($pVal['Enable']['_value'] ? '1' : '0') 
                        : '1';

                    $connections[] = [
                        'schema' => 'tr069',
                        'protocol' => 'PPPoE',
                        'path' => $path,
                        'name' => $pVal['Name']['_value'] ?? "WAN {$wIndex}/{$cIndex}/{$pIndex} (PPPoE)",
                        'enable' => $isEnabled,
                        'username' => $pVal['Username']['_value'] ?? '',
                        'password' => $pVal['Password']['_value'] ?? '',
                        'vlan_id' => $vlanVal,
                        'vlan_path' => $vlanPath,
                        'service_list' => $servVal ?? $pVal['ConnectionType']['_value'] ?? 'INTERNET',
                        'service_path' => $servPath,
                        'nat_enabled' => isset($pVal['NATEnabled']['_value']) ? ($pVal['NATEnabled']['_value'] ? '1' : '0') : '1',
                        'connection_trigger' => $pVal['ConnectionTrigger']['_value'] ?? 'AlwaysOn',
                        'mru' => $pVal['MaxMRUSize']['_value'] ?? $pVal['CurrentMRUSize']['_value'] ?? '1492',
                        'status' => $pVal['ConnectionStatus']['_value'] ?? 'Unknown',
                        'ip' => $pVal['ExternalIPAddress']['_value'] ?? '',
                        'is_active_ppp' => !empty($pVal['Username']['_value']),
                        'lan_bind' => $lanBind,
                        'ssid_bind' => $ssidBind,
                    ];
                }

                // IP Connections (some ONTs configure PPPoE under WANIPConnection with ConnectionType IP_Routed)
                $ipConns = $cVal['WANIPConnection'] ?? [];
                foreach ($ipConns as $pIndex => $pVal) {
                    if (!is_numeric($pIndex) || !is_array($pVal)) continue;
                    $path = "{$wanConnPath}.WANIPConnection.{$pIndex}";
                    $vlanPath = isset($pVal['X_HW_VLAN']['_value']) ? "{$path}.X_HW_VLAN" : (isset($pVal['VLANIDMark']['_value']) ? "{$path}.VLANIDMark" : null);
                    $vlanVal = $pVal['X_HW_VLAN']['_value'] ?? $pVal['VLANIDMark']['_value'] ?? null;
                    
                    $lanBind = ['lan1' => false, 'lan2' => false, 'lan3' => false, 'lan4' => false];
                    $ssidBind = ['ssid1' => false, 'ssid2' => false, 'ssid3' => false, 'ssid4' => false];
                    if (isset($pVal['X_HW_LANBIND']) && is_array($pVal['X_HW_LANBIND'])) {
                        $hw = $pVal['X_HW_LANBIND'];
                        for ($i = 1; $i <= 4; $i++) {
                            $lanBind["lan{$i}"] = !empty($hw["Lan{$i}Enable"]['_value']);
                            $ssidBind["ssid{$i}"] = !empty($hw["SSID{$i}Enable"]['_value']);
                        }
                    }

                    $isEnabled = isset($pVal['Enable']['_value']) 
                        ? ($pVal['Enable']['_value'] ? '1' : '0') 
                        : '1';

                    $connections[] = [
                        'schema' => 'tr069',
                        'protocol' => 'IP',
                        'path' => $path,
                        'name' => $pVal['Name']['_value'] ?? "WAN {$wIndex}/{$cIndex}/{$pIndex} (IP)",
                        'enable' => $isEnabled,
                        'username' => $pVal['Username']['_value'] ?? '',
                        'password' => $pVal['Password']['_value'] ?? '',
                        'vlan_id' => $vlanVal,
                        'vlan_path' => $vlanPath,
                        'service_list' => $pVal['X_HW_SERVICELIST']['_value'] ?? $pVal['ConnectionType']['_value'] ?? 'IP',
                        'service_path' => isset($pVal['X_HW_SERVICELIST']['_value']) ? "{$path}.X_HW_SERVICELIST" : null,
                        'nat_enabled' => isset($pVal['NATEnabled']['_value']) ? ($pVal['NATEnabled']['_value'] ? '1' : '0') : '1',
                        'connection_trigger' => $pVal['ConnectionTrigger']['_value'] ?? 'AlwaysOn',
                        'mru' => '1500',
                        'status' => $pVal['ConnectionStatus']['_value'] ?? 'Unknown',
                        'ip' => $pVal['ExternalIPAddress']['_value'] ?? '',
                        'is_active_ppp' => false,
                        'lan_bind' => $lanBind,
                        'ssid_bind' => $ssidBind,
                    ];
                }
            }
        }

        // 2. TR-181 Data Model: Device.PPP.Interface.*
        $tr181Ppp = $dev['Device']['PPP']['Interface'] ?? [];
        foreach ($tr181Ppp as $pIndex => $pVal) {
            if (!is_numeric($pIndex) || !is_array($pVal)) continue;
            $path = "Device.PPP.Interface.{$pIndex}";
            $connections[] = [
                'schema' => 'tr181',
                'protocol' => 'PPPoE',
                'path' => $path,
                'name' => $pVal['Name']['_value'] ?? "PPP Interface {$pIndex}",
                'enable' => isset($pVal['Enable']['_value']) ? ($pVal['Enable']['_value'] ? '1' : '0') : '1',
                'username' => $pVal['Username']['_value'] ?? '',
                'password' => $pVal['Password']['_value'] ?? '',
                'vlan_id' => $dev['Device']['Ethernet']['VLANTermination'][$pIndex]['VLANID']['_value'] ?? null,
                'vlan_path' => "Device.Ethernet.VLANTermination.{$pIndex}.VLANID",
                'service_list' => 'INTERNET',
                'service_path' => null,
                'nat_enabled' => '1',
                'connection_trigger' => $pVal['ConnectionTrigger']['_value'] ?? 'AlwaysOn',
                'mru' => $pVal['MaxMRUSize']['_value'] ?? '1492',
                'status' => $pVal['Status']['_value'] ?? 'Unknown',
                'ip' => $pVal['CurrentIPAddress']['_value'] ?? '',
                'is_active_ppp' => !empty($pVal['Username']['_value']),
                'lan_bind' => ['lan1' => false, 'lan2' => false, 'lan3' => false, 'lan4' => false],
                'ssid_bind' => ['ssid1' => false, 'ssid2' => false, 'ssid3' => false, 'ssid4' => false],
            ];
        }

        return $connections;
    }

    /**
     * Build parameter key-value pairs for pushing PPPoE and Network updates.
     * Automatically targets the active or user-selected WAN connection path.
     */
    public function buildPppoeParameters(array $dev, array $input, ?string $targetWanPath = null): array
    {
        $conns = $this->extractWanConnections($dev);
        $targetConn = null;

        if ($targetWanPath) {
            foreach ($conns as $c) {
                if ($c['path'] === $targetWanPath) {
                    $targetConn = $c;
                    break;
                }
            }
        }

        // Fallback: pick active PPPoE connection or first available connection
        if (!$targetConn) {
            foreach ($conns as $c) {
                if ($c['is_active_ppp']) {
                    $targetConn = $c;
                    break;
                }
            }
        }
        if (!$targetConn && !empty($conns)) {
            $targetConn = $conns[0];
        }

        $mfg = $dev['_deviceId']['_Manufacturer'] ?? $dev['DeviceID']['Manufacturer']['_value'] ?? '';
        $productClass = $dev['_deviceId']['_ProductClass'] ?? $dev['DeviceID']['ProductClass']['_value'] ?? '';
        $devId = $dev['_id'] ?? '';
        $isHuawei = stripos($mfg, 'Huawei') !== false 
            || stripos($productClass, 'HG8') !== false 
            || stripos($productClass, 'EG8') !== false 
            || stripos($devId, '00259E') !== false 
            || (isset($targetConn['vlan_path']) && str_contains($targetConn['vlan_path'], 'X_HW_'));
        $isTr181 = isset($dev['Device']) && !isset($dev['InternetGatewayDevice']);

        $path = $targetConn['path'] ?? ($isTr181 
            ? 'Device.PPP.Interface.1' 
            : ($isHuawei ? 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1' : 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1'));
        
        $params = [];

        // 1. WAN Connection Status (Enable / Disable)
        if (isset($input['is_enabled'])) {
            $params["{$path}.Enable"] = (!empty($input['is_enabled']) && $input['is_enabled'] != '0') ? "1" : "0";
        } else {
            $params["{$path}.Enable"] = "1";
        }

        // 2. PPPoE Username
        $username = $input['ppp_username'] ?? $input['username'] ?? null;
        if (!empty($username)) {
            $params["{$path}.Username"] = (string) $username;
        }

        // 3. PPPoE Password
        $password = $input['ppp_password'] ?? $input['password'] ?? null;
        if (!empty($password)) {
            $params["{$path}.Password"] = (string) $password;
        }

        // 4. VLAN ID
        if (!empty($input['vlan_id'])) {
            $vlanPath = $targetConn['vlan_path'] ?? null;
            if ($vlanPath) {
                $params[$vlanPath] = (string) $input['vlan_id'];
            } elseif ($isHuawei) {
                $params["{$path}.X_HW_VLAN"] = (string) $input['vlan_id'];
            } elseif (stripos($mfg, 'ZTE') !== false) {
                $params["{$path}.X_ZTE-COM_VLANID"] = (string) $input['vlan_id'];
            } elseif ($isTr181) {
                $params["Device.Ethernet.VLANTermination.1.VLANID"] = (string) $input['vlan_id'];
            } else {
                $params["{$path}.VLANIDMark"] = (string) $input['vlan_id'];
            }
        }

        // 5. Connection Trigger (AlwaysOn / OnDemand / Manual)
        if (!empty($input['connection_trigger'])) {
            $params["{$path}.ConnectionTrigger"] = (string) $input['connection_trigger'];
        }

        // 6. NAT Enabled
        if (isset($input['nat_enabled'])) {
            $params["{$path}.NATEnabled"] = $input['nat_enabled'] ? '1' : '0';
        }

        // 7. MRU / MTU Size
        if (!empty($input['mru'])) {
            $params["{$path}.MaxMRUSize"] = (string) $input['mru'];
        }

        // 8. Service List (INTERNET, TR069, etc.)
        if (!empty($input['service_list'])) {
            $servPath = $targetConn['service_path'] ?? ($isHuawei ? "{$path}.X_HW_SERVICELIST" : "{$path}.ServiceList");
            $params[$servPath] = (string) $input['service_list'];
        }

        // 9. Local Interface Bindings (LAN1-4 and SSID1-4)
        if (isset($input['lan_bind']) || isset($input['ssid_bind'])) {
            $lanInput = $input['lan_bind'] ?? [];
            $ssidInput = $input['ssid_bind'] ?? [];

            if ($isHuawei) {
                for ($i = 1; $i <= 4; $i++) {
                    $params["{$path}.X_HW_LANBIND.Lan{$i}Enable"] = !empty($lanInput["lan{$i}"]) ? "1" : "0";
                    $params["{$path}.X_HW_LANBIND.SSID{$i}Enable"] = !empty($ssidInput["ssid{$i}"]) ? "1" : "0";
                }
            } elseif (stripos($mfg, 'ZTE') !== false || (isset($targetConn['path']) && str_contains($targetConn['path'], 'X_ZTE-COM_'))) {
                $bindList = [];
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($lanInput["lan{$i}"])) $bindList[] = "LAN{$i}";
                }
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($ssidInput["ssid{$i}"])) $bindList[] = "SSID{$i}";
                }
                $params["{$path}.X_ZTE-COM_LANBinding"] = implode(',', $bindList);
            }
        }

        // 10. Huawei Flash Save
        if ($isHuawei) {
            $params["InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig"] = "1";
        }

        return [
            'params' => $params,
            'target_path' => $path,
            'target_conn' => $targetConn,
        ];
    }

    /**
     * Build parameter key-value pairs for Wi-Fi SSID and Password updates.
     */
    public function buildWifiParameters(array $dev, array $input, int $instance = 1): array
    {
        $mfg = $dev['_deviceId']['_Manufacturer'] ?? $dev['DeviceID']['Manufacturer']['_value'] ?? '';
        $isHuawei = stripos($mfg, 'Huawei') !== false;
        $isZte = stripos($mfg, 'ZTE') !== false;
        $isTr181 = isset($dev['Device']) && !isset($dev['InternetGatewayDevice']);

        $params = [];

        if ($isTr181) {
            if (!empty($input['wifi_ssid'])) {
                $params["Device.WiFi.SSID.{$instance}.SSID"] = (string) $input['wifi_ssid'];
                $params["Device.WiFi.Radio.{$instance}.Enable"] = "1";
            }
            if (!empty($input['wifi_password'])) {
                $params["Device.WiFi.AccessPoint.{$instance}.Security.KeyPassphrase"] = (string) $input['wifi_password'];
            }
        } else {
            if (!empty($input['wifi_ssid'])) {
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.SSID"] = (string) $input['wifi_ssid'];
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.Enable"] = "1";
            }
            if (!empty($input['wifi_password'])) {
                $pass = (string) $input['wifi_password'];
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.KeyPassphrase"] = $pass;
                $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.PreSharedKey.1.PreSharedKey"] = $pass;
                if ($isZte) {
                    $params["InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}.X_ZTE-COM_KeyPassphrase"] = $pass;
                }
            }
            if ($isHuawei) {
                $params["InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig"] = "1";
            }
        }

        return $params;
    }

    /**
     * Send a reboot command.
     */
    public function reboot(string $deviceId)
    {
        return $this->pushTask($deviceId, [
            'name' => 'reboot'
        ]);
    }

    /**
     * Send a factory reset command.
     */
    public function factoryReset(string $deviceId)
    {
        return $this->pushTask($deviceId, [
            'name' => 'factoryReset'
        ]);
    }

    /**
     * Add a tag to a device in GenieACS.
     */
    public function addTag(string $deviceId, string $tag)
    {
        $endpoint = "devices/" . urlencode($deviceId) . "/tags/" . urlencode(trim($tag));
        return $this->request('POST', $endpoint);
    }

    /**
     * Remove a tag from a device in GenieACS.
     */
    public function deleteTag(string $deviceId, string $tag)
    {
        $endpoint = "devices/" . urlencode($deviceId) . "/tags/" . urlencode(trim($tag));
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Sync/update all tags for a device in GenieACS.
     */
    public function syncTags(string $deviceId, array $newTags, array $currentTags = [])
    {
        $newTags = array_unique(array_filter(array_map('trim', $newTags)));
        $currentTags = array_unique(array_filter(array_map('trim', $currentTags)));

        $toAdd = array_diff($newTags, $currentTags);
        $toRemove = array_diff($currentTags, $newTags);

        foreach ($toRemove as $tag) {
            try {
                $this->deleteTag($deviceId, $tag);
            } catch (\Exception $e) {
                Log::warning("Failed to delete tag '{$tag}' from {$deviceId}: " . $e->getMessage());
            }
        }

        foreach ($toAdd as $tag) {
            try {
                $this->addTag($deviceId, $tag);
            } catch (\Exception $e) {
                Log::warning("Failed to add tag '{$tag}' to {$deviceId}: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Generic method to push a task to a device.
     * 
     * @param string $deviceId  The device ID
     * @param array  $task      The task payload (e.g. ['name' => 'setParameterValues', ...])
     * @param int    $phpTimeout  PHP HTTP client timeout in seconds
     * @param bool   $connectionRequest  Whether to also send a connection request to wake the device
     * @param int    $genieTimeoutMs  How long GenieACS should wait for device response (in milliseconds). 0 = don't wait.
     */
    public function pushTask(string $deviceId, array $task, int $phpTimeout = 30, bool $connectionRequest = true, int $genieTimeoutMs = 5000)
    {
        $endpoint = "devices/" . urlencode($deviceId) . "/tasks";
        
        // Build GenieACS query parameters
        $queryParts = [];
        if ($genieTimeoutMs > 0) {
            $queryParts[] = "timeout=" . $genieTimeoutMs;
        }
        if ($connectionRequest) {
            $queryParts[] = "connection_request";
        }
        if (!empty($queryParts)) {
            $endpoint .= '?' . implode('&', $queryParts);
        }
        
        return $this->request('POST', $endpoint, $task, $phpTimeout);
    }

    /**
     * Helper to perform HTTP requests.
     */
    protected function request(string $method, string $endpoint, array $data = [], int $timeout = 60)
    {
        if (!$this->baseUrl) {
            throw new Exception("ACS Server URL not set.");
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $pending = Http::timeout($timeout)->connectTimeout(min(10, $timeout));
            
            if ($method === 'GET') {
                $response = $pending->acceptJson()->get($url);
            } elseif ($method === 'POST') {
                $response = $pending->asJson()->acceptJson()->post($url, $data);
            } elseif ($method === 'PUT') {
                $response = $pending->asJson()->acceptJson()->put($url, $data);
            } elseif ($method === 'DELETE') {
                $response = $pending->acceptJson()->delete($url);
            } else {
                $response = $pending->asJson()->acceptJson()->send($method, $url, ['json' => $data]);
            }

            // For task pushes, both 200 (executed) and 202 (queued/pending) are valid
            if ($response->successful() || $response->status() === 202) {
                $totalHeader = $response->header('total')
                    ?? $response->header('total-count')
                    ?? $response->header('X-Total-Count')
                    ?? $response->header('x-total-count');

                $jsonData = $response->json();
                $totalVal = $totalHeader !== null ? (int) $totalHeader : (is_array($jsonData) ? count($jsonData) : null);

                return [
                    'data' => $jsonData,
                    'total' => $totalVal
                ];
            }

            $errorBody = $response->body();
            Log::error("GenieACS API Error [{$url}]: " . $errorBody);
            throw new Exception("GenieACS API returned error: " . $response->status() . " - " . $errorBody);

        } catch (Exception $e) {
            Log::error("GenieACS Request Exception: " . $e->getMessage());
            throw $e;
        }
    }
}
