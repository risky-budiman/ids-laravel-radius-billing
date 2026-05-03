<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customers = \App\Models\Customer::whereNotNull('olt_id')->get();
echo "Found " . count($customers) . " customers with OLT ID.\n";
foreach ($customers as $c) {
    echo "- {$c->name} (SN: {$c->sn}, Index: {$c->onu_index})\n";
}
