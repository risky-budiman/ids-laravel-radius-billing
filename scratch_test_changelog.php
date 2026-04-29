<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "Running app:generate-changelog --message='Manual Test Message' --dry-run\n";
Artisan::call('app:generate-changelog', ['--message' => 'Manual Test Message', '--dry-run' => true]);
echo Artisan::output();
