<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;

$olts = Olt::all();
foreach ($olts as $olt) {
    echo "ID: {$olt->id}, Name: {$olt->name}, IP: {$olt->ip_address}, Community: {$olt->snmp_read_community}, Port: {$olt->snmp_port}\n";
}
