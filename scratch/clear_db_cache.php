<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::table('cache')->where('key', 'like', '%noc_discovery_running%')->delete();
echo "Cache cleared from DB.\n";
