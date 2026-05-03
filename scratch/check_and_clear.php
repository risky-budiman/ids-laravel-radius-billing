<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Cache;

$isRunning = Cache::has('noc_discovery_running');
echo "Is Running: " . ($isRunning ? "YES" : "NO") . "\n";

Cache::forget('noc_discovery_running');
echo "Cache cleared.\n";
