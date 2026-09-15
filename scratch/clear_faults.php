<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server = App\Models\AcsServer::first();
$url = rtrim($server->url, '/');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url/faults");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);

$faults = json_decode($res, true) ?: [];
echo "Found " . count($faults) . " faults.\n";

foreach ($faults as $f) {
    $id = urlencode($f['_id']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url/faults/$id");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $delRes = curl_exec($ch);
    $delCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Deleted fault {$f['_id']}: HTTP $delCode\n";
}
