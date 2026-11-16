<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\OltWorkItem;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Perintah tulis ONU HSGQ lewat API web OLT. Isi permintaan dicocokkan dengan format aplikasi web OLT
 * (static/js firmware IR_V3.3.3ID); perintah ke ONU yang ada selalu didahului cek MAC di posisi itu.
 */
class HsgqOnuWriteTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private OltPonPort $port;
    private User $teknisi;
    /** @var array<int, array<string, mixed>> isi daftar izin palsu PON 2 */
    private array $allow = [];
    private array $deny = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->olt = Olt::create(['name' => 'HSGQ Uji', 'ip_address' => '10.0.0.90', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true,
            'web_username' => 'admin', 'web_password' => 'rahasia']);
        $this->port = OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 2, 'status' => 'active']);
        $this->teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->allow = [
            ['port_id' => 2, 'onu_id' => 1, 'onu_name' => 'A1', 'onu_desc' => 'Budi', 'macaddr' => 'e8:68:19:02:ff:70', 'status' => 'Online', 'auth_state' => 1],
            ['port_id' => 2, 'onu_id' => 2, 'onu_name' => '', 'onu_desc' => '', 'macaddr' => '70:89:cc:7a:6e:01', 'status' => 'Offline', 'auth_state' => 0],
        ];

        Http::fake(function (HttpRequest $r) {
            $url = $r->url();
            if (str_contains($url, 'userlogin')) {
                return Http::response(['code' => 1], 200, ['X-Token' => 'tok']);
            }
            if (str_contains($url, 'onu_allow_list?port_id') || (str_contains($url, 'onu_allow_list') && $r->method() === 'GET')) {
                return Http::response(['code' => 1, 'data' => $this->allow]);
            }
            if (str_contains($url, 'onu_deny_list')) {
                return Http::response(['code' => 1, 'data' => $this->deny]);
            }

            return Http::response(['code' => 1, 'type' => 'success', 'message' => 'ok']);
        });
    }

    private function posts(string $path): array
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $r) => $r->method() === 'POST' && str_contains($r->url(), $path))
            ->map(fn (HttpRequest $r) => $r->data())->values()->all();
    }

    public function test_reboot_sends_web_command_after_mac_check(): void
    {
        $this->actingAs($this->teknisi)
            ->postJson(route('olts.onus.reboot', [$this->olt, $this->port]), ['index' => '1/1/2:1', 'sn' => 'E8:68:19:02:FF:70'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame([['method' => 'set', 'param' => ['port_id' => 2, 'onu_id' => 1, 'flags' => 1, 'fec_mode' => 1]]],
            $this->posts('onumgmt?form=config'));
        $this->assertTrue(ActivityLog::where('action', 'onu_reboot')->exists());
    }

    public function test_command_refused_when_mac_moved_to_another_position(): void
    {
        // Layar basi: MAC Budi kini di onu_id 1, bukan 2 — ONU di posisi 2 (pelanggan lain) tidak boleh kena
        $this->actingAs($this->teknisi)
            ->postJson(route('olts.onus.reboot', [$this->olt, $this->port]), ['index' => '1/1/2:2', 'sn' => 'E8:68:19:02:FF:70'])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame([], $this->posts('onumgmt'));
    }

    public function test_rename_validates_and_sends_flags_8(): void
    {
        $this->actingAs($this->teknisi);
        $this->postJson(route('olts.onus.info', [$this->olt, $this->port]), ['index' => '1/1/2:1', 'sn' => 'e868.1902.ff70', 'name' => 'ada spasi'])
            ->assertStatus(422);

        $this->postJson(route('olts.onus.info', [$this->olt, $this->port]), ['index' => '1/1/2:1', 'sn' => 'e8681902ff70', 'name' => 'BUDI01', 'description' => 'Budi Jl. Mawar 3'])
            ->assertOk();
        $this->assertSame([['method' => 'set', 'param' => ['port_id' => 2, 'onu_id' => 1, 'flags' => 8, 'fec_mode' => 1,
            'onu_name' => 'BUDI01', 'onu_desc' => 'Budi Jl. Mawar 3']]], $this->posts('onumgmt?form=config'));
    }

    public function test_allow_new_onu_uses_customer_and_closes_work(): void
    {
        $pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        $c = Customer::create(['customer_code' => '001001000777', 'username' => 'baru@net', 'name' => 'Siti Aminah', 'package_id' => $pkg->id,
            'phone' => '08', 'onu_sn' => 'AA:BB:CC:00:11:22']);
        $c->update(['olt_id' => $this->olt->id]);
        $work = OltWorkItem::record($this->olt->id, 'provision', ['onu_sn' => 'AA:BB:CC:00:11:22', 'customer_id' => $c->id]);

        $this->actingAs($this->teknisi)
            ->postJson(route('olts.onus.allow', [$this->olt, $this->port]), ['mac' => 'aabb.cc00.1122'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame([['method' => 'add', 'param' => ['port_id' => 2, 'onu_id' => 0, 'macaddr' => 'aa:bb:cc:00:11:22', 'auth_state' => 1,
            'onu_type' => '', 'splitter_id' => 0, 'onu_desc' => 'Siti Aminah', 'onu_name' => '001001000777']]], $this->posts('onu_allow_list?form=onucfg'));
        $this->assertSame('done', $work->fresh()->status);
        $this->assertSame($this->teknisi->id, $work->fresh()->resolved_by);
    }

    public function test_allow_existing_unauthorized_sets_auth_state_and_already_allowed_sends_nothing(): void
    {
        $this->actingAs($this->teknisi);
        $this->postJson(route('olts.onus.allow', [$this->olt, $this->port]), ['mac' => '70:89:CC:7A:6E:01'])->assertOk();
        $this->assertSame([['method' => 'set', 'param' => ['port_id' => 2, 'onu_id' => 2, 'macaddr' => '70:89:cc:7a:6e:01', 'auth_state' => 1,
            'onu_type' => '', 'onu_desc' => '']]], $this->posts('onu_allow_list?form=onucfg'));

        $this->postJson(route('olts.onus.allow', [$this->olt, $this->port]), ['mac' => 'E8:68:19:02:FF:70'])
            ->assertOk()->assertJsonFragment(['message' => 'ONU sudah diizinkan di PON 1/2. ONU biasanya online ±1 menit kemudian.']);
        $this->assertCount(1, $this->posts('onu_allow_list?form=onucfg'));

        $this->postJson(route('olts.onus.allow', [$this->olt, $this->port]), ['mac' => 'bukan-mac'])->assertStatus(422);
    }

    public function test_pending_lists_unauthorized_and_denied(): void
    {
        $this->deny = [['port_id' => 2, 'macaddr' => '11:22:33:44:55:66', 'reason' => 'nyasar']];
        $res = $this->actingAs($this->teknisi)->getJson(route('olts.onus.pending', [$this->olt, $this->port]))->assertOk();
        $this->assertSame(['70:89:CC:7A:6E:01', '11:22:33:44:55:66'], collect($res->json('onus'))->pluck('mac')->all());
        $this->assertSame(['unauthorized', 'denied'], collect($res->json('onus'))->pluck('source')->all());
    }

    public function test_delete_and_run_work_from_panel(): void
    {
        $this->actingAs($this->teknisi);
        $del = OltWorkItem::record($this->olt->id, 'delete', ['onu_sn' => 'E8:68:19:02:FF:70']);
        $this->post(route('olts.work.run', [$this->olt, $del]))->assertRedirect()->assertSessionHas('success');
        $this->assertSame([['method' => 'delete', 'param' => ['port_id' => 2, 'onu_id' => 1, 'macaddr' => 'e8:68:19:02:ff:70']]],
            $this->posts('onu_allow_list?form=onucfg'));
        $this->assertSame('done', $del->fresh()->status);

        $prov = OltWorkItem::record($this->olt->id, 'provision', ['onu_sn' => '0A0B0C0D0E0F']);
        $this->post(route('olts.work.run', [$this->olt, $prov]), ['port_id' => $this->port->id])->assertSessionHas('success');
        $this->assertSame('done', $prov->fresh()->status);
        $this->assertSame('add', $this->posts('onu_allow_list?form=onucfg')[1]['method']);

        // Detail OLT menampilkan tombol kerja langsung
        OltWorkItem::record($this->olt->id, 'provision', ['onu_sn' => '0A0B0C0D0E99']);
        $this->get(route('olts.show', $this->olt))->assertSee('Allow sekarang');
    }

    public function test_write_actions_refused_without_web_access_or_on_zte(): void
    {
        $zte = Olt::create(['name' => 'ZTE', 'ip_address' => '10.0.0.91', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'ZTE_C320', 'is_active' => true]);
        $zp = OltPonPort::create(['olt_id' => $zte->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        $this->actingAs($this->teknisi);
        $this->postJson(route('olts.onus.allow', [$zte, $zp]), ['mac' => 'AA:BB:CC:00:11:22'])->assertStatus(422);

        $this->olt->update(['web_password' => null]);
        $this->postJson(route('olts.onus.reboot', [$this->olt, $this->port]), ['index' => '1/1/2:1', 'sn' => 'E8:68:19:02:FF:70'])->assertStatus(422);
        $this->assertSame([], $this->posts('onumgmt'));
    }
}
