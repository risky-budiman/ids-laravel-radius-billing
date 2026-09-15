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

$provisions = $method->invoke($service, 'GET', 'provisions');
foreach ($provisions['data'] as $p) {
    echo "=== PROVISION {$p['_id']} ===\n";
    $lines = explode("\n", $p['script']);
    foreach ($lines as $lineNum => $l) {
        if (preg_match('/\b(refresh|refreshObject|clear)\b/i', $l)) {
            echo "Line " . ($lineNum + 1) . ": " . trim($l) . "\n";
        }
    }
}
