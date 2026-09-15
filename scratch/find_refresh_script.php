<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$service = App\Services\GenieACSService::forServer($server);

$reflector = new ReflectionClass($service);
$method = $reflector->getMethod('request');
$method->setAccessible(true);

echo "=== CHECK PRESETS ===\n";
$presets = $method->invoke($service, 'GET', 'presets');
echo json_encode($presets, JSON_PRETTY_PRINT) . "\n";

echo "=== CHECK PROVISIONS ===\n";
$provisions = $method->invoke($service, 'GET', 'provisions');
foreach ($provisions['data'] as $p) {
    echo "Provision: {$p['_id']}\n";
    if (stripos($p['script'], 'refresh') !== false) {
        echo "--> HAS 'refresh':\n" . $p['script'] . "\n";
    }
}
