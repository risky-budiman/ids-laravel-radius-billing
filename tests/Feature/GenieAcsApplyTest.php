<?php

namespace Tests\Feature;

use App\Models\AcsServer;
use App\Models\User;
use App\Services\GenieACSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pengiriman perubahan ke modem: hanya parameter yang ada, bertipe benar, hasil sebenarnya dilaporkan,
 * perintah yang ditolak modem dihapus; antrean menampilkan perintah gagal & bisa dihapus.
 */
class GenieAcsApplyTest extends TestCase
{
    use RefreshDatabase;

    private const ID = '00259E-HG8546M-485754432E22ED9B';

    private function device(): array
    {
        $w = fn ($v, $t = 'xsd:string') => ['_value' => $v, '_writable' => true, '_type' => $t];
        $ppp = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1';

        return [
            '_id' => self::ID, '_deviceId' => ['_Manufacturer' => 'Huawei Technologies Co., Ltd', '_ProductClass' => 'HG8546M'],
            'InternetGatewayDevice' => [
                'LANDevice' => [1 => ['WLANConfiguration' => [1 => ['SSID' => $w('Lama'), 'Enable' => $w(true, 'xsd:boolean'),
                    'KeyPassphrase' => $w(''), 'PreSharedKey' => [1 => ['KeyPassphrase' => $w(''), 'PreSharedKey' => $w('')]]]]]],
                'WANDevice' => [1 => ['WANConnectionDevice' => [2 => ['WANPPPConnection' => [1 => [
                    'Enable' => $w(true, 'xsd:boolean'), 'Username' => $w('lama@net'), 'Password' => $w(''),
                    'X_HW_VLAN' => $w(101, 'xsd:unsignedInt'), 'ConnectionStatus' => ['_value' => 'Connected'],
                    'NATEnabled' => $w(true, 'xsd:boolean'),
                ]]]]]],
                // Tanpa X_Huawei_SelfDefined.SaveConfig & X_HW_LANBIND (seperti sebagian HG8546M)
            ],
        ];
    }

    public function test_only_existing_parameters_with_device_types_are_sent(): void
    {
        $prep = (new GenieACSService('http://acs.test'))->prepareParameterValues($this->device(), [
            'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable' => '1',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.X_HW_VLAN' => '200',
            'InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig' => '1',
        ]);
        $this->assertSame([
            ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable', true, 'xsd:boolean'],
            ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.X_HW_VLAN', 200, 'xsd:unsignedInt'],
        ], $prep['values']);
        $this->assertSame(['InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig'], $prep['skipped']);
    }

    public function test_wan_change_applied_reports_done_and_skips_unknown_parameters(): void
    {
        $server = AcsServer::create(['name' => 'Pusat', 'url' => 'http://acs.test', 'is_active' => true]);
        Http::fake(function (HttpRequest $r) {
            if ($r->method() === 'POST') {
                return Http::response(['_id' => 'task1'], 200);   // dijalankan modem
            }

            return Http::response([$this->device()]);
        });
        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));

        $res = $this->postJson(route('acs-servers.update-config', ['deviceId' => self::ID]), [
            'server_id' => $server->id, 'wan_path' => 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1',
            'is_enabled' => '1', 'ppp_username' => 'baru@net', 'vlan_id' => '101', 'nat_enabled' => '1',
            'lan_bind' => ['lan1' => '1'], 'ssid_bind' => [],
        ])->assertOk()->assertJson(['success' => true, 'state' => 'done']);
        $this->assertStringContainsString('dilewati karena tidak ada di modem', $res->json('message'));

        Http::assertSent(function (HttpRequest $r) {
            if ($r->method() !== 'POST') {
                return false;
            }
            $names = collect($r->data()['parameterValues'])->pluck(0);

            return $names->contains('InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username')
                && ! $names->contains(fn ($n) => str_contains($n, 'X_HW_LANBIND') || str_contains($n, 'SaveConfig'));
        });
    }

    public function test_rejected_change_is_reported_and_removed_from_the_queue(): void
    {
        $server = AcsServer::create(['name' => 'Pusat', 'url' => 'http://acs.test', 'is_active' => true]);
        Http::fake(function (HttpRequest $r) {
            if ($r->method() === 'POST') {
                return Http::response(['_id' => 'task9'], 202);
            }
            if ($r->method() === 'DELETE') {
                return Http::response('', 200);
            }
            if (str_contains($r->url(), '/faults')) {
                return Http::response([['_id' => self::ID . ':task_task9', 'code' => 'cwmp.9007', 'message' => 'Invalid parameter value', 'retries' => 0]]);
            }

            return Http::response([$this->device()]);
        });
        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));

        $this->post(route('acs-servers.update-config', ['deviceId' => self::ID]), [
            'server_id' => $server->id, 'instance' => 1, 'wifi_ssid' => 'RumahBaru', 'wifi_password' => 'katasandi123',
        ])->assertRedirect()->assertSessionHas('error', 'Wi-Fi 1: Modem menolak perubahan: nilai parameter tidak valid (cwmp.9007)')
            ->assertSessionHas('acs_tab', 'tab-wireless');

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/tasks/task9'));
        $this->assertFalse(\App\Models\ActivityLog::where('action', 'acs_config_updated')->exists(), 'perubahan yang ditolak tidak dicatat sebagai berhasil');
    }

    public function test_queue_shows_failed_tasks_and_they_can_be_deleted(): void
    {
        $server = AcsServer::create(['name' => 'Pusat', 'url' => 'http://acs.test', 'is_active' => true]);
        Http::fake(function (HttpRequest $r) {
            if ($r->method() === 'DELETE') {
                return Http::response('', 200);
            }
            if (str_contains($r->url(), '/faults')) {
                return Http::response([['_id' => self::ID . ':task_t1', 'code' => 'cwmp.9002', 'message' => 'Internal error', 'retries' => 12]]);
            }
            if (str_contains($r->url(), '/tasks')) {
                return Http::response([['_id' => 't1', 'name' => 'setParameterValues', 'device' => self::ID, 'timestamp' => now()->toIso8601String()]]);
            }

            return Http::response([$this->device()]);
        });
        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));

        $this->get(route('acs-servers.device-details', ['deviceId' => self::ID, 'server_id' => $server->id]))->assertOk()
            ->assertSee('Gagal')->assertSee('kesalahan internal modem')->assertSee('dicoba 12x')->assertSee('Hapus perintah');
        $this->getJson(route('acs-servers.queue', ['deviceId' => self::ID, 'server_id' => $server->id]))->assertOk()
            ->assertJsonPath('tasks.0.name', 'setParameterValues')->assertJsonPath('tasks.0.fault', 'kesalahan internal modem (biasanya ada parameter/nilai yang tidak diterima) (cwmp.9002) · dicoba 12x');

        $this->post(route('acs-servers.delete-task', ['deviceId' => self::ID, 'taskId' => 't1']), ['server_id' => $server->id])
            ->assertSessionHas('success', 'Perintah dihapus dari antrean modem.');
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/tasks/t1'));
    }
}
