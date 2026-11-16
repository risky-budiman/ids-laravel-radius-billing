<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\CustomerSignalLog;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\Package;
use App\Models\User;
use App\Services\Network\NocService;
use App\Services\Network\OltGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * NOC Center: angka dasbor benar, sinkron ONU memakai jalur per port (HSGQ & ZTE) dengan SN dinormalkan,
 * pencarian ONU belum terdaftar tanpa antrean, halaman sinyal & riwayat.
 */
class NocCenterTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private Package $pkg;
    private object $gw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pkg = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        $this->olt = Olt::create(['name' => 'OLT Gotong Royong', 'ip_address' => '10.0.0.80', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true]);
        OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);

        // ONU di OLT: MAC huruf kecil (seperti SNMP HSGQ), pelanggan menyimpan huruf besar
        $this->gw = new class($this->olt) extends OltGateway {
            public array $rows = [];
            public function getOnusOnPort(int $shelf, int $slot, int $port): array { return $this->rows; }
            public function getOltStatus(): array { return ['status' => 'online', 'cpu' => 9, 'temp' => 35, 'uptime' => '40d']; }
        };
        $this->gw->rows = [
            ['index' => '1.1.1.1', 'sn' => 'e8:68:19:02:ff:70', 'status' => 'online', 'signal' => -18.5],
            ['index' => '1.1.1.2', 'sn' => '00:1a:69:43:12:29', 'status' => 'offline', 'signal' => 'N/A'],
            ['index' => '1.1.1.3', 'sn' => '70:89:cc:7a:6e:01', 'status' => 'online', 'signal' => -28.3],
        ];
        $this->app->bind(OltGateway::class, fn () => $this->gw);
    }

    private function customer(string $code, string $sn, string $status = Customer::STATUS_ACTIVE, bool $withOlt = true): Customer
    {
        $c = Customer::create(['customer_code' => $code, 'username' => "{$code}@net", 'name' => "Pelanggan {$code}",
            'package_id' => $this->pkg->id, 'phone' => '08', 'onu_sn' => $sn, 'onu_index' => '1/1/1:9']);
        $c->forceFill(['status' => $status, 'is_active' => $status === Customer::STATUS_ACTIVE, 'olt_id' => $withOlt ? $this->olt->id : null])->saveQuietly();

        return $c->fresh();
    }

    private function radiusSession(string $username): void
    {
        DB::table('radacct')->insert(['acctsessionid' => uniqid(), 'acctuniqueid' => uniqid(), 'username' => $username, 'acctstarttime' => now()]);
    }

    public function test_sync_matches_normalized_sn_links_missing_olt_and_flags_not_found(): void
    {
        $a = $this->customer('A1', 'E8:68:19:02:FF:70');
        $b = $this->customer('B1', '001A69431229');                       // tanpa pemisah
        $c = $this->customer('C1', '70-89-CC-7A-6E-01', withOlt: false);    // impor: OLT belum diisi
        $d = $this->customer('D1', 'AA:BB:CC:DD:EE:FF');                  // tidak ada di OLT

        $r = app(NocService::class)->syncAll();
        $this->assertSame(3, $r['matched']);
        $this->assertSame(1, $r['not_found']);

        $this->assertSame(['online', -18.5, '1.1.1.1'], [$a->signalCache->status, $a->signalCache->rx_power, $a->signalCache->onu_index]);
        $this->assertSame('offline', $b->fresh()->signalCache->status);
        $this->assertSame($this->olt->id, $c->fresh()->olt_id, 'pelanggan hasil impor tertaut ke OLT lewat SN');
        $this->assertSame('not_found', $d->fresh()->signalCache->status);
        $this->assertSame('1.1.1.1', $a->fresh()->onu_index, 'posisi ONU pelanggan diperbarui');
        $this->assertTrue(CustomerSignalLog::where('customer_id', $a->id)->exists());
    }

    public function test_dashboard_counts_only_active_customers_and_lists_problems(): void
    {
        $a = $this->customer('A1', 'E8:68:19:02:FF:70');
        $b = $this->customer('B1', '00:1A:69:43:12:29');
        $s = $this->customer('S1', '70:89:CC:7A:6E:01', Customer::STATUS_SUSPENDED);
        $this->radiusSession($a->username);
        $this->radiusSession($a->username);          // sesi ganda tidak dihitung dua kali
        $this->radiusSession($s->username);          // terisolir tersambung
        $this->radiusSession('bukan-pelanggan');     // sesi lain
        app(NocService::class)->syncAll();

        $d = app(NocService::class)->dashboard();
        $this->assertSame([2, 1, 1, 1], [$d['customers_active'], $d['pppoe_online'], $d['pppoe_offline'], $d['isolated_online']]);
        $this->assertSame([3, 2, 1, 1], [$d['onu_tracked'], $d['onu_online'], $d['onu_down'], $d['signal_critical']]);

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('noc.index'))->assertOk()
            ->assertSee('NOC Center')->assertSee('OLT Gotong Royong')->assertSee('Pelanggan B1')   // ONU aktif mati
            ->assertSee('Sinkron ONU');
        $this->actingAs($teknisi)->postJson(route('noc.sync'))->assertOk()->assertJson(['success' => true])
            ->assertJsonFragment(['message' => '3 ONU terbaca, 3 pelanggan diperbarui.']);
    }

    public function test_signals_page_filters_and_history_chart(): void
    {
        $a = $this->customer('A1', 'E8:68:19:02:FF:70');
        $this->customer('C1', '70:89:CC:7A:6E:01');
        $this->customer('N1', 'AB:AB:AB:AB:AB:AB');
        app(NocService::class)->syncAll();
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->get(route('noc.signals', ['filter' => 'critical']))->assertOk()->assertSee('Pelanggan C1')->assertDontSee('Pelanggan A1')->assertSee('-28.30 dBm');
        $this->get(route('noc.signals', ['filter' => 'not_found']))->assertOk()->assertSee('Pelanggan N1');
        $this->get(route('noc.signals', ['q' => 'A1']))->assertOk()->assertSee('Pelanggan A1')->assertDontSee('Pelanggan C1');

        CustomerSignalLog::create(['customer_id' => $a->id, 'rx_power' => -19.0, 'status' => 'online', 'created_at' => now()->subHours(3)]);
        $this->get(route('noc.history', [$a->id, 'days' => 1]))->assertOk()->assertSee('Grafik daya terima')->assertSee('<polyline', false);
    }

    public function test_discovery_uses_hsgq_web_pending_list_without_queue(): void
    {
        $this->customer('A1', 'AA:BB:CC:00:11:22');
        $this->olt->update(['web_username' => 'admin', 'web_password' => 'rahasia']);
        Http::fake(function ($r) {
            if (str_contains($r->url(), 'userlogin')) {
                return Http::response(['code' => 1], 200, ['X-Token' => 't']);
            }
            if (str_contains($r->url(), 'onu_deny_list')) {
                return Http::response(['code' => 1, 'data' => []]);
            }

            return Http::response(['code' => 1, 'data' => [['onu_id' => 5, 'macaddr' => 'aa:bb:cc:00:11:22', 'auth_state' => 0]]]);
        });
        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($administrator);

        $this->get(route('noc.discovery'))->assertOk()->assertSee('Belum dipindai');
        $this->postJson(route('noc.discovery.scan'))->assertOk()->assertJson(['success' => true, 'count' => 1]);
        $this->get(route('noc.discovery'))->assertOk()->assertSee('AA:BB:CC:00:11:22')->assertSee('Pelanggan A1')->assertSee('Allow');

        // OLT HSGQ tanpa akses web: dilaporkan, bukan "0 ONU"
        $this->olt->update(['web_password' => null]);
        $this->postJson(route('noc.discovery.scan'))->assertOk()->assertJson(['count' => 0])
            ->assertJsonPath('data.olts.0.state', 'unsupported');
    }
}
