<?php

namespace App\Services;

use App\Models\AcsServer;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieACSService
{
    protected ?string $baseUrl;

    /** Login NBI (Basic Auth, mis. lewat reverse proxy nginx); null = NBI tanpa login. */
    protected ?array $auth = null;

    public function __construct(?string $baseUrl = null, ?string $username = null, ?string $password = null)
    {
        $this->baseUrl = $baseUrl;
        if (filled($username)) {
            $this->auth = [(string) $username, (string) $password];
        }
    }

    /**
     * Set the target ACS server model.
     */
    public static function forServer(AcsServer $server): self
    {
        return new self($server->url, $server->nbi_username, $server->nbi_password);
    }

    /**
     * Set the target URL directly.
     */
    public static function forUrl(string $url): self
    {
        return new self($url);
    }

    /**
     * Catat aksi ke modem pelanggan di Log Aktivitas (dulu ubah PPPoE/WiFi, reboot & factory reset tidak
     * tercatat). Nilai parameter yang berisi password/kunci WiFi disamarkan.
     *
     * @param  array<string, mixed>  $params  path parameter TR-069 => nilai (bentuk yang dikirim ke setParameters)
     */
    public static function audit(string $action, string $deviceId, array $params = [], array $extra = []): void
    {
        $changes = [];
        foreach ($params as $name => $value) {
            $changes[$name] = preg_match('/pass|key|secret/i', (string) $name) ? '••••' : (is_array($value) ? ($value[0] ?? null) : $value);
        }
        $labels = [
            'acs_config_updated' => 'Konfigurasi modem diubah',
            'acs_rebooted' => 'Modem di-reboot',
            'acs_factory_reset' => 'Modem di-factory reset',
            'acs_tags_updated' => 'Tag modem diubah',
        ];
        \App\Models\ActivityLog::log($action, ($labels[$action] ?? $action) . " ({$deviceId})", null,
            array_filter(['device_id' => $deviceId, 'parameters' => $changes ?: null] + $extra, fn ($v) => $v !== null));
    }

    /** Field yang dicari oleh kotak pencarian perangkat (nilai = jenis kata kunci). */
    private const SEARCH_FIELDS = [
        '_id' => 'text', '_tags' => 'text', 'Tags' => 'text', 'VirtualParameters.tag' => 'text', 'VirtualParameters.tags' => 'text',
        '_deviceId._SerialNumber' => 'text', 'DeviceID.SerialNumber' => 'text',
        'Device.DeviceInfo.SerialNumber' => 'text', 'InternetGatewayDevice.DeviceInfo.SerialNumber' => 'text',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username' => 'idpel',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username' => 'idpel',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username' => 'idpel',
        'Device.PPP.Interface.1.Username' => 'idpel', 'Device.PPP.Interface.2.Username' => 'idpel',
        'VirtualParameters.pppUsername' => 'idpel',
        'VirtualParameters.IP' => 'text', 'VirtualParameters.wanip' => 'text',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress' => 'text',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress' => 'text',
        'Device.IP.Interface.1.IPv4Address.1.IPAddress' => 'text',
    ];

    /**
     * Query MongoDB (NBI GenieACS) untuk kotak pencarian & filter tag.
     *
     * Dulu dikirim string "/kata/i": NBI meneruskannya ke MongoDB sebagai teks biasa (bukan regex), sehingga
     * hanya kecocokan persis yang ditemukan. Sekarang memakai $regex dengan kata kunci yang di-escape
     * (karakter seperti "." atau "(" tidak lagi dianggap pola).
     */
    public static function searchQuery(?string $search, ?string $tag = null): array
    {
        $contains = fn (string $s) => ['$regex' => preg_quote($s, '/'), '$options' => 'i'];
        $query = [];

        $tag = trim((string) $tag);
        if ($tag !== '') {
            $query['_tags'] = $contains($tag);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $idpel = explode('@', $search)[0];
            $or = [['_tags' => $search]];
            foreach (self::SEARCH_FIELDS as $field => $kind) {
                $or[] = [$field => $contains($kind === 'idpel' ? $idpel : $search)];
            }
            $query = $query ? ['$and' => [['_tags' => $query['_tags']], ['$or' => $or]]] : ['$or' => $or];
        }

        return $query;
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

    /** Kode fault CWMP yang umum, untuk pesan ke pengguna. */
    private const CWMP_FAULTS = [
        '9001' => 'permintaan ditolak modem', '9002' => 'kesalahan internal modem (biasanya ada parameter/nilai yang tidak diterima)',
        '9003' => 'argumen tidak valid', '9005' => 'nama parameter tidak dikenal modem', '9006' => 'tipe nilai salah',
        '9007' => 'nilai parameter tidak valid', '9008' => 'parameter tidak bisa diubah',
    ];

    /** Node parameter di data perangkat (null bila tidak ada). */
    public static function node(array $dev, string $path): ?array
    {
        $node = $dev;
        foreach (explode('.', $path) as $key) {
            if (! is_array($node) || ! array_key_exists($key, $node)) {
                return null;
            }
            $node = $node[$key];
        }

        return is_array($node) ? $node : null;
    }

    /**
     * Saring parameter terhadap data modem: hanya yang ADA dan BISA DITULIS yang dikirim, dengan tipe dari data
     * modem (xsd:boolean → true/false, angka → int). Dulu parameter vendor yang tidak ada di modem (mis.
     * X_Huawei_SelfDefined.SaveConfig) ikut terkirim sehingga modem menolak seluruh perintah.
     *
     * @param  array<string, mixed>  $params
     * @return array{values: list<array>, skipped: list<string>}
     */
    public function prepareParameterValues(array $dev, array $params): array
    {
        $values = [];
        $skipped = [];
        foreach ($params as $name => $value) {
            if (! $dev) {   // data modem tidak terbaca: kirim apa adanya
                $values[] = [$name, $value];

                continue;
            }
            $node = self::node($dev, $name);
            if ($node === null || ($node['_writable'] ?? true) === false) {
                $skipped[] = $name;

                continue;
            }
            $type = $node['_type'] ?? null;
            if ($type === 'xsd:boolean') {
                $value = in_array($value, [true, 1, '1', 'true'], true);
            } elseif (in_array($type, ['xsd:unsignedInt', 'xsd:int', 'xsd:long', 'xsd:unsignedLong'], true) && is_numeric($value)) {
                $value = (int) $value;
            } else {
                $value = (string) $value;
            }
            $values[] = $type ? [$name, $value, $type] : [$name, $value];
        }

        return ['values' => $values, 'skipped' => $skipped];
    }

    /**
     * Kirim perubahan parameter dan laporkan hasil yang sebenarnya. Perintah yang ditolak modem dihapus dari
     * antrean (dulu tertahan & diulang terus, menghambat perintah lain untuk modem itu).
     *
     * @return array{state: string, message: string, skipped: list<string>, sent: list<string>}  state: done | queued | failed
     */
    public function applyParameters(string $deviceId, array $dev, array $params): array
    {
        $prep = $this->prepareParameterValues($dev, $params);
        $sent = array_map(fn ($v) => $v[0], $prep['values']);
        if (! $prep['values']) {
            return ['state' => 'failed', 'message' => 'Tidak ada parameter yang dikenali modem ini untuk diubah. Klik Sinkron data lalu coba lagi.',
                'skipped' => $prep['skipped'], 'sent' => []];
        }

        $task = ['name' => 'setParameterValues', 'parameterValues' => $prep['values']];
        try {
            $res = $this->pushTask($deviceId, $task, 15, true, 8000);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'cURL error 28') || stripos($e->getMessage(), 'timed out') !== false) {
                return ['state' => 'queued', 'message' => 'Perintah terkirim; modem sedang memprosesnya.', 'skipped' => $prep['skipped'], 'sent' => $sent];
            }
            throw $e;
        }

        if (($res['status'] ?? 200) === 200) {
            return ['state' => 'done', 'message' => 'Perubahan sudah diterapkan di modem.', 'skipped' => $prep['skipped'], 'sent' => $sent];
        }

        // 202: belum selesai — cek apakah modem menolak
        $taskId = $res['data']['_id'] ?? null;
        $fault = $taskId ? $this->taskFault($deviceId, (string) $taskId) : null;
        if ($fault) {
            try {
                $this->request('DELETE', 'tasks/' . rawurlencode((string) $taskId));
            } catch (Exception) {
            }

            return ['state' => 'failed', 'message' => 'Modem menolak perubahan: ' . $fault, 'skipped' => $prep['skipped'], 'sent' => $sent];
        }

        return ['state' => 'queued', 'message' => 'Perintah terkirim; modem belum terhubung ke GenieACS dan akan menerapkannya saat terhubung.',
            'skipped' => $prep['skipped'], 'sent' => $sent];
    }

    /** Pesan fault (bahasa Indonesia) untuk satu tugas, atau null. */
    public function taskFault(string $deviceId, string $taskId): ?string
    {
        try {
            $faults = $this->request('GET', 'faults/?' . http_build_query(['query' => json_encode(['_id' => "{$deviceId}:task_{$taskId}"])]))['data'] ?? [];
        } catch (Exception) {
            return null;
        }
        $f = $faults[0] ?? null;

        return $f ? self::describeFault($f) : null;
    }

    /** Fault per tugas untuk satu perangkat: [task_id => pesan]. */
    public function deviceFaults(string $deviceId): array
    {
        $out = [];
        try {
            foreach ((array) ($this->request('GET', 'faults/?' . http_build_query(['query' => json_encode(['device' => $deviceId])]))['data'] ?? []) as $f) {
                if (preg_match('/:task_(.+)$/', (string) ($f['_id'] ?? ''), $m)) {
                    $out[$m[1]] = self::describeFault($f) . ' · dicoba ' . (int) ($f['retries'] ?? 0) . 'x';
                }
            }
        } catch (Exception) {
        }

        return $out;
    }

    public static function describeFault(array $f): string
    {
        $code = (string) ($f['code'] ?? '');
        $num = preg_replace('/\D/', '', $code);
        $text = self::CWMP_FAULTS[$num] ?? (string) ($f['message'] ?? $code);

        return trim($text . ($code ? " ({$code})" : ''));
    }

    /** Hapus satu tugas (beserta faultnya) dari antrean modem. */
    public function deleteTask(string $deviceId, string $taskId): void
    {
        $this->request('DELETE', 'tasks/' . rawurlencode($taskId));
        try {
            $this->request('DELETE', 'faults/' . rawurlencode("{$deviceId}:task_{$taskId}"));
        } catch (Exception) {
            // fault ikut hilang bersama tugas di sebagian versi GenieACS
        }
    }

    /**
     * Minta modem membaca ulang seluruh parameternya. Membaca semua parameter bisa lebih dari 30 detik dan
     * NBI menahan balasan selama itu (dulu tampil "Gagal menghubungi server GenieACS"); perintah sudah tercatat
     * di antrean begitu dikirim, jadi tidak ditunggu sampai selesai.
     *
     * @return string done (selesai dijalankan) | queued (tercatat, sedang/akan diproses modem)
     */
    public function refreshDevice(string $deviceId, string $rootObject): string
    {
        $task = ['name' => 'refreshObject', 'objectName' => rtrim($rootObject, '.')];
        try {
            $res = $this->pushTask($deviceId, $task, 12, true, 3000);

            return ($res['data']['_id'] ?? null) && empty($res['data']['fault']) && ($res['status'] ?? 200) === 200 ? 'done' : 'queued';
        } catch (Exception $e) {
            // Balasan habis waktu: anggap terkirim bila perintahnya memang sudah ada di antrean modem
            $timedOut = str_contains($e->getMessage(), 'cURL error 28') || stripos($e->getMessage(), 'timed out') !== false;
            if ($timedOut) {
                try {
                    foreach ((array) ($this->getTasks($deviceId)['data'] ?? []) as $t) {
                        if (($t['name'] ?? '') === 'refreshObject' && ($t['objectName'] ?? '') === $task['objectName']) {
                            return 'queued';
                        }
                    }
                } catch (Exception) {
                }
            }
            throw $e;
        }
    }

    /**
     * Hapus perintah refreshObject rusak dari versi lama aplikasi (nama objek berakhiran titik → fault
     * "Invalid parameter path" yang tertahan di antrean modem). Hanya perintah sinkron buatan aplikasi yang
     * cocok persis yang dihapus; perintah lain tidak disentuh. @return int jumlah yang dihapus
     */
    public function deleteBrokenRefreshTasks(string $deviceId): int
    {
        $deleted = 0;
        try {
            foreach ((array) ($this->getTasks($deviceId)['data'] ?? []) as $task) {
                $name = (string) ($task['objectName'] ?? '');
                if (($task['name'] ?? '') === 'refreshObject' && in_array($name, ['InternetGatewayDevice.', 'Device.'], true) && ! empty($task['_id'])) {
                    $this->request('DELETE', 'tasks/' . rawurlencode((string) $task['_id']));
                    $deleted++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Bersihkan perintah sinkron lama {$deviceId} gagal: " . $e->getMessage());
        }

        return $deleted;
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

        // 10. Huawei Flash Save (dikirim hanya bila parameternya ada di modem — lihat prepareParameterValues)
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
        $mfg = $dev['_deviceId']['_Manufacturer'] ?? $dev['DeviceID']['Manufacturer']['_value']
            ?? $dev['Device']['DeviceInfo']['Manufacturer']['_value'] ?? $dev['InternetGatewayDevice']['DeviceInfo']['Manufacturer']['_value'] ?? '';
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
                $base = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$instance}";
                // Password Wi-Fi (passphrase 8–63 karakter). PreSharedKey.1.PreSharedKey khusus kunci heksa 64
                // karakter: dulu password biasa ikut dikirim ke sana sehingga Huawei menolak seluruh perintah (9002).
                $has = fn ($p) => $dev && self::node($dev, $p) !== null;
                if (preg_match('/^[0-9a-fA-F]{64}$/', $pass)) {
                    $params["{$base}.PreSharedKey.1.PreSharedKey"] = $pass;
                } else {
                    // Satu parameter passphrase standar saja (yang ada di modem)
                    $std = $has("{$base}.PreSharedKey.1.KeyPassphrase") ? "{$base}.PreSharedKey.1.KeyPassphrase" : "{$base}.KeyPassphrase";
                    $params[$std] = $pass;
                    if ($isZte && $has("{$base}.X_ZTE-COM_KeyPassphrase")) {
                        $params["{$base}.X_ZTE-COM_KeyPassphrase"] = $pass;
                    }
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
        $endpoint = "devices/" . rawurlencode($deviceId) . "/tags/" . rawurlencode(trim($tag));
        return $this->request('POST', $endpoint);
    }

    /**
     * Remove a tag from a device in GenieACS.
     */
    public function deleteTag(string $deviceId, string $tag)
    {
        $endpoint = "devices/" . rawurlencode($deviceId) . "/tags/" . rawurlencode(trim($tag));
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
        $endpoint = "devices/" . rawurlencode($deviceId) . "/tasks";
        
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
            throw new Exception('Alamat server GenieACS belum diisi.');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $pending = Http::timeout($timeout)->connectTimeout(min(10, $timeout));
            if ($this->auth) {
                $pending = $pending->withBasicAuth($this->auth[0], $this->auth[1]);
            }

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
                    'status' => $response->status(),   // 200 = tugas dijalankan, 202 = masih di antrean
                    'data' => $jsonData,
                    'total' => $totalVal
                ];
            }

            if (in_array($response->status(), [401, 403], true)) {
                throw new Exception($this->auth
                    ? 'Login NBI GenieACS ditolak (periksa username/password di Server ACS).'
                    : 'GenieACS meminta login (isi username/password NBI di Server ACS).');
            }
            $errorBody = $response->body();
            Log::error("GenieACS API Error [{$url}]: " . $errorBody);
            $detail = $response->json('message') ?? $response->json('error') ?? \Illuminate\Support\Str::limit(trim(strip_tags($errorBody)), 160);
            throw new Exception(match (true) {
                $response->status() === 404 => 'Data tidak ditemukan di GenieACS (HTTP 404)' . ($detail ? ": {$detail}" : '.'),
                $response->status() >= 500 => "GenieACS mengalami galat (HTTP {$response->status()})" . ($detail ? ": {$detail}" : '.'),
                default => "GenieACS menolak permintaan (HTTP {$response->status()})" . ($detail ? ": {$detail}" : '.'),
            });

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("GenieACS Request Exception: " . $e->getMessage());
            // Teks "cURL error 28 / timed out" dipertahankan: dipakai untuk mengenali waktu habis (refresh/kirim parameter)
            $timedOut = str_contains($e->getMessage(), 'cURL error 28') || stripos($e->getMessage(), 'timed out') !== false;
            throw new Exception($timedOut
                ? "GenieACS tidak menjawab dalam {$timeout} detik (cURL error 28, timed out)."
                : 'Tidak bisa terhubung ke GenieACS di ' . $this->baseUrl . '. Periksa alamat, port & jaringan server. (' . \Illuminate\Support\Str::limit($e->getMessage(), 120) . ')', 0, $e);
        } catch (Exception $e) {
            Log::error("GenieACS Request Exception: " . $e->getMessage());
            throw $e;
        }
    }
}
