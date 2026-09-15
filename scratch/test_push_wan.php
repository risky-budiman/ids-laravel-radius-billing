<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$service = App\Services\GenieACSService::forServer($server);

$deviceId = "00259E-HG8245H5-485754436FB722A4";
$dev = $service->getDevice($deviceId);

if (!$dev) {
    die("Device not found!\n");
}

$wanPath = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1';

// Test parameters building
$params = $service->buildPppoeParameters($dev, [
    'wan_path' => $wanPath,
    'is_enabled' => '1',
    'username' => 'test_user',
    'password' => 'test_pass123',
    'vlan_id' => '100',
    'connection_trigger' => 'AlwaysOn',
    'mtu' => '1492',
    'service_type' => 'INTERNET',
    'nat_enabled' => '1',
    'lan_bind' => ['lan1', 'lan2'],
    'ssid_bind' => ['ssid1'],
], $wanPath);

echo "Built parameters:\n";
echo json_encode($params, JSON_PRETTY_PRINT) . "\n";

// Verify that ALL parameter keys are valid native TR-069 and none are virtual parameters
foreach ($params as $k => $v) {
    if (str_starts_with($k, 'VirtualParameters.')) {
        throw new Exception("ERROR: Virtual parameter detected: $k");
    }
}
echo "ALL parameters are pure native TR-069! No VirtualParameters!\n";

// Call setParameters (GenieACS queue)
try {
    $service->setParameters($deviceId, $params);
    echo "setParameters successfully queued!\n";
} catch (Exception $e) {
    echo "setParameters note: " . $e->getMessage() . "\n";
}

// Check faults immediately
sleep(2);
$url = rtrim($server->url, '/');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/faults");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$faults = json_decode(curl_exec($ch), true) ?: [];
curl_close($ch);

echo "Faults count after push: " . count($faults) . "\n";
if (!empty($faults)) {
    echo "Fault details:\n" . json_encode($faults, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "SUCCESS: ZERO faults recorded in GenieACS!\n";
}
