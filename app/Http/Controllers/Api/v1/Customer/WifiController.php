<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\AcsServer;
use App\Services\GenieACSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WifiController extends Controller
{
    /**
     * Retrieve the customer's CPE device from GenieACS.
     */
    protected function getCustomerDevice(Request $request)
    {
        $customer = $request->user();
        $server = AcsServer::preferred();   // dulu server pertama walau nonaktif
        
        if (!$server) {
            return [
                'error' => 'ACS Server not configured on system.'
            ];
        }

        $service = GenieACSService::forServer($server);
        
        // Find by Serial Number (onu_sn) or active PPPoE Username
        $query = [];
        if ($customer->onu_sn) {
            $query['DeviceID.SerialNumber'] = $customer->onu_sn;
        } elseif ($customer->username) {
            // Try standard TR-069 PPPoE username paths first, then VP fallback
            $query['$or'] = [
                ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username' => $customer->username],
                ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username' => $customer->username],
                ['Device.PPP.Interface.1.Username' => $customer->username],
                ['VirtualParameters.pppUsername' => $customer->username],
            ];
        }

        if (empty($query)) {
            return [
                'error' => 'No Serial Number or PPPoE Username registered for this account.'
            ];
        }

        $devicesResponse = $service->getDevices($query, [], 0, 1);
        $devices = $devicesResponse['data'] ?? [];

        // If not found by exact serial, try ID suffix query (case-insensitive)
        if (empty($devices) && $customer->onu_sn) {
            $devicesResponse = $service->getDevices(['_id' => ['$regex' => $customer->onu_sn . '$', '$options' => 'i']], [], 0, 1);
            $devices = $devicesResponse['data'] ?? [];
        }

        if (empty($devices)) {
            return [
                'error' => 'CPE Device not found on the ACS server.'
            ];
        }

        return [
            'device' => $devices[0],
            'service' => $service,
            'server' => $server
        ];
    }

    /**
     * Get Wi-Fi SSID settings.
     */
    public function getSettings(Request $request)
    {
        $res = $this->getCustomerDevice($request);
        if (isset($res['error'])) {
            return response()->json(['message' => $res['error']], 404);
        }

        $device = $res['device'];
        $deviceId = $device['_id'];

        $ssids = [];
        for ($i = 1; $i <= 1; $i++) {
            $igdWlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][$i] ?? null;
            $devWlanSsid = $device['Device']['WiFi']['SSID'][$i] ?? null;
            $devWlanRadio = $device['Device']['WiFi']['Radio'][$i] ?? null;

            if (!$igdWlan && !$devWlanSsid) {
                continue;
            }

            $ssid = $igdWlan['SSID']['_value'] ?? $devWlanSsid['SSID']['_value'] ?? 'Unknown';
            $band = $devWlanRadio['OperatingFrequencyBand']['_value'] ?? ($i == 2 ? '5 GHz' : '2.4 GHz');
            
            // Check if enabled (either 1 or true)
            $rawEnabled = $igdWlan['Enable']['_value'] ?? $devWlanRadio['Enable']['_value'] ?? null;
            $enabled = ($rawEnabled === '1' || $rawEnabled === 1 || $rawEnabled === true || $rawEnabled === 'true');

            $ssids[] = [
                'instance' => $i,
                'ssid' => $ssid,
                'band' => $band,
                'enabled' => $enabled
            ];
        }

        return response()->json([
            'device_id' => $deviceId,
            'ssids' => $ssids
        ]);
    }

    /**
     * Update Wi-Fi settings (SSID / Password).
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'instance' => 'required|integer|in:1',
            'wifi_ssid' => 'required|string|min:1|max:32',
            'wifi_password' => 'nullable|string|min:8|max:64',
        ]);

        $res = $this->getCustomerDevice($request);
        if (isset($res['error'])) {
            return response()->json(['message' => $res['error']], 404);
        }

        $device = $res['device'];
        $deviceId = $device['_id'];
        $service = $res['service'];
        $instance = $request->instance;

        // Parameter WiFi dari logika yang sama dengan halaman admin. Dulu aplikasi pelanggan selalu mengirim
        // parameter khusus ZTE (X_ZTE-COM_KeyPassphrase) ke semua merek, sehingga modem lain bisa menolak
        // seluruh perubahan.
        $params = $service->buildWifiParameters($device, $request->only(['wifi_ssid', 'wifi_password']), (int) $instance);

        try {
            // Hanya parameter yang ada di modem; hasil sebenarnya dilaporkan (dulu selalu "berhasil")
            $result = $service->applyParameters($deviceId, $device, $params);
            if ($result['state'] === 'failed') {
                return response()->json(['success' => false, 'message' => 'Modem belum bisa mengubah Wi-Fi saat ini. Coba lagi nanti atau hubungi kami.'], 422);
            }
            $customer = $request->user();
            GenieACSService::audit('acs_config_updated', $deviceId, array_intersect_key($params, array_flip($result['sent'])),
                ['by_customer' => $customer->customer_code ?? $customer->id, 'via' => 'aplikasi pelanggan', 'result' => $result['state']]);

            return response()->json([
                'success' => true,
                'state' => $result['state'],
                'message' => $result['state'] === 'done'
                    ? 'Wi-Fi sudah diubah. Sambungkan ulang perangkat Anda dengan nama/password baru.'
                    : 'Perubahan Wi-Fi terkirim ke modem dan akan diterapkan dalam beberapa saat.',
            ]);
        } catch (\Exception $e) {
            Log::error("API Wifi Update Error for {$deviceId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah konfigurasi Wi-Fi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get modem device status (signal, uptime, temperature, online status).
     */
    public function getDeviceStatus(Request $request)
    {
        $res = $this->getCustomerDevice($request);
        if (isset($res['error'])) {
            return response()->json(['message' => $res['error']], 404);
        }

        $device = $res['device'];

        // 1. Signal / Rx Power (standard vendor paths first, VP fallback)
        $rxPowerRaw = $device['InternetGatewayDevice']['WANDevice'][1]['X_GponInterafceConfig']['RXPower']['_value']
            ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_HW_OpticalInfo']['RxPower']['_value']
            ?? $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['X_ZTE-COM_WANPONInterfaceConfig']['RXPower']['_value']
            ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_ZTE-COM_Optical']['RxPower']['_value']
            ?? $device['InternetGatewayDevice']['X_ALU_OntOpticalParam']['RXPower']['_value']
            ?? $device['Device']['Optical']['Interface'][1]['OpticalSignalLevel']['_value']
            ?? $device['Device']['Optical']['Interface'][1]['OpticalPowerRx']['_value']
            ?? $device['VirtualParameters']['getponrx']['_value']
            ?? null;
        $rxPower = null;
        if ($rxPowerRaw !== null) {
            $rxPower = (float) $rxPowerRaw;
            if ($rxPower < -100) $rxPower = $rxPower / 100;
            if ($rxPower < -1000) $rxPower = $rxPower / 10;
        }

        // 2. Uptime
        $uptime = $device['InternetGatewayDevice']['DeviceInfo']['UpTime']['_value']
            ?? $device['Device']['DeviceInfo']['UpTime']['_value']
            ?? null;

        // 3. Suhu / Temperature
        $tempRaw = $device['InternetGatewayDevice']['WANDevice'][1]['X_GponInterafceConfig']['TransceiverTemperature']['_value']
            ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_HW_OpticalInfo']['Temperature']['_value']
            ?? $device['InternetGatewayDevice']['WANDevice'][1]['X_ZTE-COM_Optical']['Temp']['_value']
            ?? null;
        $temperature = null;
        if ($tempRaw !== null) {
            $temperature = (float) $tempRaw;
            if ($temperature > 200) $temperature = $temperature / 100;
            if ($temperature > 1000) $temperature = $temperature / 10;
        }

        // 4. Status (Online / Offline)
        $isOnline = false;
        if (isset($device['_lastInform'])) {
            $lastInformTime = strtotime($device['_lastInform']);
            if (time() - $lastInformTime < 300) { // 5 minutes
                $isOnline = true;
            }
        }

        return response()->json([
            'success' => true,
            'signal' => $rxPower,
            'uptime' => $uptime ? (int)$uptime : null,
            'temperature' => $temperature,
            'is_online' => $isOnline
        ]);
    }
}
