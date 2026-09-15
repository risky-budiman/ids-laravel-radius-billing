<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$url = rtrim($server->url, '/');

// Test 1: Get device's tasks
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/tasks?query=" . urlencode(json_encode(['device' => '00259E-HG8546M-485754432E22ED9B'])));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Tasks for device: Code $code\nBody: $res\n\n";

// Test 2: Get specific task
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/tasks?query=" . urlencode(json_encode(['_id' => 'task_6aa9a8b918a768ada2e7484e'])));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Specific task: Code $code\nBody: $res\n\n";

// Test 3: What collections exist on NBI?
foreach (['tasks', 'devices', 'presets', 'provisions', 'files', 'faults'] as $coll) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url/$coll?limit=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Endpoint /$coll: Code $code\n";
}
