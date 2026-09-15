<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$url = rtrim($server->url, '/');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/faults?limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Faults Code: $code\n";
echo "Faults Body: " . json_encode(json_decode($res), JSON_PRETTY_PRINT) . "\n";
