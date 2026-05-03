<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Services\Network\SnmpService;
use App\Services\Network\OltDiscoveryService;
use App\Services\Network\ZteOltProvisioningService;

$olt = Olt::where('is_active', true)->first();
if (!$olt) {
    echo "No active OLT found.\n";
    exit;
}

echo "Testing OLT: {$olt->name} ({$olt->ip_address})\n";

try {
    $snmp = new SnmpService($olt->ip_address, $olt->snmp_read_community, $olt->snmp_port);
    $discovery = new OltDiscoveryService($snmp);
    $onus = $discovery->scanUnconfiguredOnus();
    echo "SNMP Found: " . count($onus) . " ONUs\n";

    if (empty($onus)) {
        echo "Trying Telnet Fallback...\n";
        $provisioning = new ZteOltProvisioningService($olt);
        $onus = $provisioning->getUnconfiguredOnus();
        echo "Telnet Found: " . count($onus) . " ONUs\n";
    }

    foreach ($onus as $onu) {
        echo "- SN: {$onu['sn']} at {$onu['full_index']}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
