<?php

namespace Tests\Feature;

use App\Models\Olt;
use App\Services\Network\HsgqOids;
use App\Services\Network\OltGateway;
use App\Services\Network\SnmpService;
use Tests\TestCase;

/**
 * Server Linux dengan ekstensi php-snmp: hasil walk harus berkunci OID numerik penuh. Dulu
 * snmp_set_oid_numeric_print(1) (= SNMP_OID_OUTPUT_SUFFIX) membuat MAC & Rx ONU HSGQ tidak pernah cocok:
 * 239 ONU terbaca tanpa MAC dan semuanya "offline" di produksi.
 */
class SnmpNativeExtensionTest extends TestCase
{
    public function test_numeric_keys_are_kept_and_other_formats_rejected(): void
    {
        $this->assertSame(['.1.3.6.1.2' => 'x', '.1.3.6.1.3' => '5'],
            SnmpService::numericOidKeys(['.1.3.6.1.2' => '"x"', '1.3.6.1.3' => '5']));
        $this->assertNull(SnmpService::numericOidKeys(['iso.3.6.1.4.1.50224.3.3.2.1.7.16777473' => "\xE8h\x19\x02\xFFp"]));
        $this->assertNull(SnmpService::numericOidKeys(['SNMPv2-SMI::enterprises.50224.3.3.2.1.2.16777473' => 'a']));
    }

    public function test_hsgq_onus_get_mac_and_status_from_native_extension_output(): void
    {
        $if = HsgqOids::PON_INDEX_BASE + 1;   // PON 1, ONU 1
        $native = [
            ltrim(HsgqOids::ONU_NAME_TABLE, '.') . ".{$if}" => '"20391582228"',
            HsgqOids::ONU_MAC_TABLE . ".{$if}" => "\xE8\x68\x19\x02\xFF\x70",          // 6 byte mentah (SNMP_VALUE_PLAIN)
            HsgqOids::ONU_RX_POWER_TABLE . ".{$if}.0.0" => '-1563',
            HsgqOids::ONU_DISTANCE_TABLE . ".{$if}" => '850',
        ];
        $snmp = new class('10.0.0.1') extends SnmpService {
            public array $rows = [];
            protected function nativeWalk(string $target, string $oid, int $timeoutMicro): ?array
            {
                $prefix = ltrim($oid, '.');
                $hit = array_filter($this->rows, fn ($k) => str_starts_with(ltrim($k, '.'), $prefix), ARRAY_FILTER_USE_KEY);

                return $hit ?: null;
            }
        };
        $snmp->rows = $native;

        $olt = new Olt(['name' => 'OLT', 'ip_address' => '10.0.0.1', 'olt_type' => 'HSGQ_EPON']);
        $gw = new class($olt) extends OltGateway {
            public SnmpService $fake;
            public function snmp(): SnmpService { return $this->fake; }
        };
        $gw->fake = $snmp;

        $onus = $gw->getOnusOnPort(1, 1, 1);
        $this->assertCount(1, $onus);
        $this->assertSame(['20391582228', 'e8:68:19:02:ff:70', 'online', -15.63],
            [$onus[0]['name'], $onus[0]['sn'], $onus[0]['status'], $onus[0]['signal']]);
    }
}
