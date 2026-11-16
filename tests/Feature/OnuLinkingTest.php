<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\Package;
use App\Models\User;
use App\Services\Network\NocService;
use App\Services\Network\OltGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Pelanggan lama (tanpa MAC ONU & OLT) bisa ditautkan: otomatis lewat nama ONU = username/kode pelanggan,
 * usulan dari deskripsi ONU = nama pelanggan, atau manual (cari pelanggan) — dan bisa dilepas.
 */
class OnuLinkingTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private OltPonPort $port;
    private Package $pkg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        $this->olt = Olt::create(['name' => 'OLT A', 'ip_address' => '10.0.0.81', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true]);
        $this->port = OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);

        // Bentuk data OLT HSGQ asli: nama ONU = ID PPPoE, identitas = MAC
        $rows = [
            ['index' => '1.1.1.1', 'name' => '20391582228', 'sn' => '00:1a:69:43:12:29', 'status' => 'online', 'signal' => -15.6],
            ['index' => '1.1.1.2', 'name' => 'XYZ', 'sn' => 'e8:68:19:02:ff:70', 'status' => 'online', 'signal' => -21.0],
            ['index' => '1.1.1.3', 'name' => 'ONU-LAIN', 'sn' => '68:9f:f0:03:f7:38', 'status' => 'offline', 'signal' => 'N/A'],
        ];
        $gw = new class($this->olt) extends OltGateway {
            public array $rows = [];
            public function getOnusOnPort(int $shelf, int $slot, int $port): array { return $this->rows; }
            public function getOltStatus(): array { return ['status' => 'online']; }
        };
        $gw->rows = $rows;
        $this->app->bind(OltGateway::class, fn () => $gw);
        Cache::put("olt_port_data_{$this->port->id}", $rows, 600);
        // Deskripsi dari web OLT (dikunci MAC tanpa pemisah)
        Cache::put("olt_port_desc_{$this->port->id}", ['E8681902FF70' => ['name' => 'XYZ', 'description' => 'Raihan Abdillah']], 600);
    }

    private function customer(string $code, string $username, string $name): Customer
    {
        $c = Customer::create(['customer_code' => $code, 'username' => $username, 'name' => $name, 'package_id' => $this->pkg->id, 'phone' => '08']);
        $c->forceFill(['status' => Customer::STATUS_ACTIVE, 'is_active' => true])->saveQuietly();

        return $c->fresh();
    }

    public function test_sync_links_existing_customers_by_onu_name(): void
    {
        $budi = $this->customer('001001000001', '20391582228@nextlink', 'Budi');   // pelanggan lama tanpa OLT/MAC

        $r = app(NocService::class)->syncAll();
        $this->assertSame(1, $r['matched']);
        $budi->refresh();
        $this->assertSame([$this->olt->id, '00:1A:69:43:12:29', '1.1.1.1'], [$budi->olt_id, $budi->onu_sn, $budi->onu_index]);
        $this->assertSame('online', $budi->signalCache->status);

        // Nama ONU yang dipakai dua pelanggan tidak ditautkan (ambigu)
        $a = $this->customer('ONU-LAIN', 'x@net', 'A');
        $b = $this->customer('001001000099', 'onu-lain@net', 'B');
        app(NocService::class)->syncAll();
        $this->assertNull($a->fresh()->onu_sn);
        $this->assertNull($b->fresh()->onu_sn);
    }

    public function test_linking_page_suggests_and_links(): void
    {
        $this->customer('001001000001', '20391582228@nextlink', 'Budi');
        $raihan = $this->customer('001001000002', '20391582226@nextlink', 'Raihan Abdillah');   // cocok lewat deskripsi
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->get(route('noc.linking'))->assertOk()
            ->assertSee('Penautan ONU')->assertSee('Budi')->assertSee('nama ONU = username/kode pelanggan')
            ->assertSee('Raihan Abdillah')->assertSee('deskripsi ONU = nama pelanggan')
            ->assertSee('Tautkan semua usulan (2)');

        $this->post(route('noc.link-suggested'), ['olt_id' => $this->olt->id])->assertSessionHas('success');
        $this->assertSame('E8:68:19:02:FF:70', $raihan->fresh()->onu_sn);
        $this->assertSame($this->olt->id, $raihan->fresh()->olt_id);
        $this->get(route('noc.linking', ['tab' => 'linked']))->assertOk()->assertSee('Raihan Abdillah')->assertSee('Lepas');
    }

    public function test_manual_link_unlink_and_conflict(): void
    {
        $siti = $this->customer('001001000003', 'siti@net', 'Siti');
        $joko = $this->customer('001001000004', 'joko@net', 'Joko');
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->postJson(route('noc.link'), ['olt_id' => $this->olt->id, 'index' => '1.1.1.3', 'customer_id' => $siti->id])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame('68:9F:F0:03:F7:38', $siti->fresh()->onu_sn);
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'onu_linked')->exists());

        // ONU yang sama tidak boleh dipakai pelanggan lain
        $this->postJson(route('noc.link'), ['olt_id' => $this->olt->id, 'index' => '1.1.1.3', 'customer_id' => $joko->id])
            ->assertStatus(422)->assertJsonFragment(['success' => false]);

        $this->postJson(route('noc.unlink', $siti))->assertOk();
        $this->assertNull($siti->fresh()->onu_sn);

        // Port page menampilkan pelanggan yang cocok lewat nama ONU (sebelum disimpan)
        $this->customer('001001000005', '20391582228@nextlink', 'Budi');
        $res = $this->getJson(route('olts.get-port-data', [$this->olt, $this->port]))->assertOk();
        $this->assertSame('Budi', $res->json('onus.0.customer.name'));
        $this->assertFalse($res->json('onus.0.customer.linked'));
    }
}
