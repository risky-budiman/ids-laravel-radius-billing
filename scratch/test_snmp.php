<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

$oltId = 3; // OLT ID from logs
$olt = Olt::find($oltId);

if (!$olt) {
    die("OLT not found\n");
}

$ip = $olt->ip_address;
$community = $olt->snmp_community;
$oid = ".1.3.6.1.4.1.3902.1012.3.28.1.1.3"; // Description OID

echo "Testing SNMP on {$ip} with community {$community}...\n";

// Try executing snmpwalk
$command = "snmpwalk -v2c -c {$community} {$ip} {$oid} 2>&1";
echo "Command: {$command}\n";
$output = shell_exec($command);

if ($output) {
    echo "Output obtained:\n";
    echo $output;
} else {
    echo "No output obtained (check if snmpwalk is installed).\n";
}
