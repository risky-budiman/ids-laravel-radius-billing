<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customers = \App\Models\Customer::all();
$output = "Total Customers: " . count($customers) . "\n";
foreach ($customers as $c) {
    $output .= "- ID: {$c->id}, Name: {$c->name}, OLT: " . ($c->olt_id ?: 'NULL') . ", Index: " . ($c->onu_index ?: 'NULL') . ", SN: " . ($c->onu_sn ?: 'NULL') . "\n";
}

file_put_contents(__DIR__ . '/db_check.txt', $output);
echo "Check finished. Results in scratch/db_check.txt\n";
