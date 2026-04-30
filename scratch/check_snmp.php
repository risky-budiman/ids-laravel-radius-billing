<?php
// Autoload composer dependencies
require __DIR__ . '/../../vendor/autoload.php';

use App\Services\Network\SnmpService;

// Setup manual test variables
$ip = '10.254.1.2'; // IP OLT
$community = 'public'; // Community string
$version = 2; // Version 2

echo "Testing SNMP to $ip with community '$community' (v$version)...\n";

try {
    $snmp = new SnmpService($ip, $community, 161, $version);
    
    echo "1. Testing sysDescr (Standard): ";
    $sys = $snmp->get('1.3.6.1.2.1.1.1.0');
    echo $sys . "\n";

    echo "2. Testing ZTE Port List (ZTE OID): \n";
    $ports = $snmp->walk('1.3.6.1.4.1.3902.1012.3.1.2.1.1.3');
    print_r($ports);

    echo "3. Testing Standard Port List (ifName): \n";
    $standard = $snmp->walk('1.3.6.1.2.1.31.1.1.1.1');
    print_r($standard);

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
