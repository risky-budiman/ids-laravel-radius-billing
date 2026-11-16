<?php

namespace Tests\Feature;

use App\Models\AcsServer;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Services\GenieACSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Login NBI GenieACS (Basic Auth) & logika WiFi aplikasi pelanggan yang sama dengan admin.
 */
class GenieAcsNbiAuthTest extends TestCase
{
    use RefreshDatabase;

    private const HUAWEI = [
        '_id' => '00E0FC-HG8245H-HWTC0001',
        '_deviceId' => ['_Manufacturer' => 'Huawei Technologies Co., Ltd'],
        'InternetGatewayDevice' => ['LANDevice' => [1 => ['WLANConfiguration' => [1 => ['SSID' => ['_value' => 'Lama']]]]]],
    ];

    public function test_basic_auth_is_sent_only_when_configured_and_401_is_explained(): void
    {
        Http::fake(['acs-a.test/*' => Http::response([], 200), 'acs-b.test/*' => Http::response([], 200), 'acs-c.test/*' => Http::response('Unauthorized', 401)]);
        $a = AcsServer::create(['name' => 'A', 'url' => 'http://acs-a.test', 'nbi_username' => 'nbi', 'nbi_password' => 'Rahasia#1', 'is_active' => true]);
        $b = AcsServer::create(['name' => 'B', 'url' => 'http://acs-b.test', 'is_active' => true]);

        GenieACSService::forServer($a)->getDevices();
        GenieACSService::forServer($b)->getDevices();
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'acs-a.test') && $r->header('Authorization') === ['Basic ' . base64_encode('nbi:Rahasia#1')]);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'acs-b.test') && ! $r->hasHeader('Authorization'));

        $c = AcsServer::create(['name' => 'C', 'url' => 'http://acs-c.test', 'is_active' => true]);
        try {
            GenieACSService::forServer($c)->getDevices();
            $this->fail('401 harus melempar exception');
        } catch (\Exception $e) {
            $this->assertStringContainsString('GenieACS meminta login', $e->getMessage());
        }

        // Tersimpan terenkripsi & tidak ikut terserialisasi
        $this->assertNotSame('Rahasia#1', DB::table('acs_servers')->where('id', $a->id)->value('nbi_password'));
        $this->assertArrayNotHasKey('nbi_password', $a->fresh()->toArray());
    }

    public function test_admin_screen_saves_keeps_and_clears_login_and_tests_connection(): void
    {
        Http::fake(['acs.test/*' => Http::response([['_id' => 'X']], 200, ['total' => '12'])]);
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin);

        $this->post(route('acs-servers.store'), ['name' => 'Pusat', 'url' => 'http://acs.test', 'nbi_username' => 'nbi',
            'nbi_password' => 'Rahasia#1', 'is_active' => 1])->assertRedirect();
        $server = AcsServer::sole();
        $this->assertSame('Rahasia#1', $server->nbi_password);

        // Password kosong saat ubah = tetap
        $this->put(route('acs-servers.update', $server), ['name' => 'Pusat 2', 'url' => 'http://acs.test', 'nbi_username' => 'nbi', 'nbi_password' => '', 'is_active' => 1]);
        $this->assertSame(['Pusat 2', 'Rahasia#1'], [$server->fresh()->name, $server->fresh()->nbi_password]);

        $this->get(route('acs-servers.index'))->assertOk()->assertSee('Dengan login NBI')->assertDontSee('Rahasia#1');
        $this->post(route('acs-servers.test', $server))->assertSessionHas('success', 'Terhubung ke Pusat 2 (12 perangkat).');

        $this->put(route('acs-servers.update', $server), ['name' => 'Pusat 2', 'url' => 'http://acs.test', 'clear_nbi_auth' => 1, 'is_active' => 1]);
        $this->assertNull($server->fresh()->nbi_username);
        $this->assertNull($server->fresh()->nbi_password);
    }

    public function test_admin_api_never_returns_the_password(): void
    {
        Http::fake(['acs.test/*' => Http::response([], 200, ['total' => '0'])]);
        AcsServer::create(['name' => 'Pusat', 'url' => 'http://acs.test', 'nbi_username' => 'nbi', 'nbi_password' => 'Rahasia#1', 'is_active' => true]);
        Sanctum::actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));

        $res = $this->getJson('/api/v1/admin/genieacs/servers');
        $res->assertOk()->assertJsonPath('servers.0.has_nbi_auth', true);
        $this->assertStringNotContainsString('Rahasia', $res->getContent());
    }

    public function test_customer_wifi_uses_admin_logic_per_brand_and_is_logged(): void
    {
        Http::fake(['acs.test/*' => Http::sequence()->push([self::HUAWEI], 200)->push([], 200)]);
        AcsServer::create(['name' => 'Lama', 'url' => 'http://nonaktif.test', 'is_active' => false]);   // dulu server pertama dipakai
        AcsServer::create(['name' => 'Pusat', 'url' => 'http://acs.test', 'is_active' => true]);
        $pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        $customer = Customer::create(['customer_code' => '001001000999', 'username' => 'wifi@net', 'name' => 'Pelanggan WiFi',
            'package_id' => $pkg->id, 'phone' => '08', 'onu_sn' => 'HWTC0001']);
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/customer/wifi/settings', ['instance' => 1, 'wifi_ssid' => 'RumahBaru', 'wifi_password' => 'katasandi123'])
            ->assertOk()->assertJson(['success' => true]);

        Http::assertSent(function (HttpRequest $r) {
            if ($r->method() !== 'POST' || ! str_contains($r->url(), 'acs.test')) {
                return false;
            }
            $names = collect($r->data()['parameterValues'] ?? [])->pluck(0)->all();

            return in_array('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID', $names, true)
                && in_array('InternetGatewayDevice.Services.X_Huawei_SelfDefined.SaveConfig', $names, true)
                && ! collect($names)->contains(fn ($n) => str_contains($n, 'X_ZTE-COM'));   // dulu dikirim ke semua merek
        });
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'nonaktif.test'));

        $log = ActivityLog::where('action', 'acs_config_updated')->sole();
        $this->assertNull($log->user_id, 'pelanggan bukan pengguna staf');
        $this->assertSame('001001000999', $log->properties['by_customer']);
        $this->assertSame('••••', $log->properties['parameters']['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase']);
    }
}
