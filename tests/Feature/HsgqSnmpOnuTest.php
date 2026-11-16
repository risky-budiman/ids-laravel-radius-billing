<?php

namespace Tests\Feature;

use App\Models\Olt;
use App\Services\Network\HsgqOids;
use App\Services\Network\OltGateway;
use App\Services\Network\SnmpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daftar ONU HSGQ lewat SNMP. Data uji = cuplikan snmpwalk HSGQ-E04ID asli (dicocokkan dengan CLI pada 238 ONU).
 */
class HsgqSnmpOnuTest extends TestCase
{
    use RefreshDatabase;

    private function olt(): Olt
    {
        return Olt::create(['name' => 'HSGQ', 'ip_address' => '10.0.0.10', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true]);
    }

    /** SNMP palsu berisi baris dari walk asli (PON1 ONU 1–2, PON2 ONU 1; ONU 3 PON1 tanpa data optik). */
    private function gateway(Olt $olt, array $tables): OltGateway
    {
        $snmp = new class($tables) extends SnmpService {
            public function __construct(private array $tables) { parent::__construct('127.0.0.1'); }
            public function walkSafe(string $oid): array { return $this->tables[$oid] ?? []; }
            public function walkHexSafe(string $oid): array { return $this->tables[$oid . ':hex'] ?? []; }
        };

        return new class($olt, $snmp) extends OltGateway {
            public function __construct(Olt $olt, private SnmpService $fake) { parent::__construct($olt); }
            public function snmp(): SnmpService { return $this->fake; }
        };
    }

    private function realTables(): array
    {
        $n = HsgqOids::ONU_NAME_TABLE;
        $m = HsgqOids::ONU_MAC_TABLE;
        $r = HsgqOids::ONU_RX_POWER_TABLE;
        $d = HsgqOids::ONU_DISTANCE_TABLE;

        return [
            $n => ["{$n}.16777473" => '20391582228', "{$n}.16777474" => '20391582226', "{$n}.16777475" => '20391582237', "{$n}.16777729" => '203911643043'],
            "{$m}:hex" => ["{$m}.16777473" => '001a69431229', "{$m}.16777474" => 'e8681902ff70', "{$m}.16777475" => '689ff003f738', "{$m}.16777729" => 'ccbce3b97546'],
            $r => [
                "{$r}.16777472.65535.65535" => HsgqOids::OPTICAL_NA,   // transceiver port PON, bukan ONU
                "{$r}.16777473.0.0" => -1563, "{$r}.16777474.0.0" => -1497, "{$r}.16777729.0.0" => -2065,
            ],
            $d => ["{$d}.16777473" => 644, "{$d}.16777474" => 465],
        ];
    }

    public function test_onus_are_read_per_port_from_snmp(): void
    {
        $onus = $this->gateway($this->olt(), $this->realTables())->getOnusOnPort(1, 1, 1);

        $this->assertCount(3, $onus, 'ONU port lain (16777729 = PON2) tidak ikut');
        $this->assertSame(['index' => '1.1.1.1', 'onu_id' => 1, 'sn' => '00:1a:69:43:12:29', 'name' => '20391582228'],
            array_intersect_key($onus[0], array_flip(['index', 'onu_id', 'sn', 'name'])));
        $this->assertSame(-15.63, $onus[0]['signal']);       // CLI: -15.6067 dBm
        $this->assertSame('online', $onus[0]['status']);
        $this->assertSame(644, $onus[0]['distance']);
        $this->assertSame(-14.97, $onus[1]['signal']);
        // Tanpa data optik → offline
        $this->assertSame('offline', $onus[2]['status']);
        $this->assertSame('N/A', $onus[2]['signal']);

        $pon2 = $this->gateway($this->olt(), $this->realTables())->getOnusOnPort(1, 1, 2);
        $this->assertSame(['1.1.2.1'], array_column($pon2, 'index'));
        $this->assertSame('cc:bc:e3:b9:75:46', $pon2[0]['sn']);   // MAC ini tampil sebagai STRING acak di snmpwalk biasa
    }

    public function test_unreadable_table_fails_clearly(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak terbaca');
        $this->gateway($this->olt(), [])->getOnusOnPort(1, 1, 1);
    }

    public function test_hex_walk_normalises_raw_bytes_and_hex_text(): void
    {
        $svc = new class('127.0.0.1') extends SnmpService {
            public function walk(string $oid): array
            {
                return ['.1.1' => "\x00\x1a\x69\x43\x12\x29", '.1.2' => '00 1A 69 43 12 29', '.1.3' => "\xcc\xbc\xe3\xb9\x75\x46"];
            }
        };
        $this->assertSame(['.1.1' => '001a69431229', '.1.2' => '001a69431229', '.1.3' => 'ccbce3b97546'], $svc->walkHexSafe('.1'));
    }
}
