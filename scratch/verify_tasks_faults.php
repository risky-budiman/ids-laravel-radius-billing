<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$url = rtrim($server->url, '/');

// Check Faults
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/faults");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$faults = json_decode(curl_exec($ch), true) ?: [];
curl_close($ch);

echo "Active Faults Count: " . count($faults) . "\n";
if (!empty($faults)) {
    echo json_encode($faults, JSON_PRETTY_PRINT) . "\n";
}

// Check Tasks
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/tasks?limit=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$tasks = json_decode(curl_exec($ch), true) ?: [];
curl_close($ch);

echo "Recent Tasks Count: " . count($tasks) . "\n";
foreach ($tasks as $t) {
    echo "Task [{$t['_id']}] Device: {$t['device']} Name: {$t['name']}\n";
}
