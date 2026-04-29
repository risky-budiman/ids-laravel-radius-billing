<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Changelog;

$logs = Changelog::all();
echo "Total logs: " . $logs->count() . "\n";
foreach ($logs as $log) {
    echo "[{$log->version}] {$log->title} - {$log->release_date}\n";
    echo "Description: " . $log->description . "\n";
}
