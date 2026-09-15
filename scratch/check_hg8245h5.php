<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$service = App\Services\GenieACSService::forServer($server);

$devId = '00259E-HG8245H5-485754436FB722A4';
$devRes = $service->getDevice($devId);
$dev = $devRes['data'] ?? [];

echo "=== EXTRACTED WAN FOR $devId ===\n";
$conns = $service->extractWanConnections($dev);
print_r($conns);

echo "=== RAW WAN Connection Devices ===\n";
$connDevs = $dev['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'] ?? [];
echo "Keys of WANConnectionDevice: " . json_encode(array_keys($connDevs)) . "\n";
