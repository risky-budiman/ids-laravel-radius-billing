<?php

namespace Tests\Feature;

use App\Models\AcsServer;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\GenieACSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GenieACS: pencarian memakai $regex yang di-escape, ID berspasi bisa dibuka,
 * hak akses web = API (administrator & teknisi), aksi ke modem tercatat.
 */
class GenieAcsTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE_ID = '00259E-ZXHN%20F670L-ZTEGC1234567';

    private AcsServer $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = AcsServer::create(['name' => 'ACS', 'url' => 'http://acs.test:7557', 'is_active' => true]);
        Http::fake(['acs.test:7557/*' => Http::response([['_id' => self::DEVICE_ID]], 200, ['total' => '1'])]);
    }

    public function test_search_query_uses_escaped_regex(): void
    {
        $q = GenieACSService::searchQuery('ZTEG.1(', 'odp-01');

        $this->assertSame(['$regex' => 'odp\-01', '$options' => 'i'], $q['$and'][0]['_tags']);
        $or = $q['$and'][1]['$or'];
        $this->assertContains(['_id' => ['$regex' => 'ZTEG\.1\(', '$options' => 'i']], $or);
        // Kata kunci tidak lagi dikirim sebagai string "/kata/i"
        $this->assertStringNotContainsString('/i', json_encode($q));
        // Username PPPoE dicari tanpa domain
        $this->assertContains(['VirtualParameters.pppUsername' => ['$regex' => '001001001863', '$options' => 'i']],
            GenieACSService::searchQuery('001001001863@nextlink')['$or']);
    }

    public function test_teknisi_can_open_devices_and_ids_with_spaces_work(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->get(route('acs-servers.devices', ['q' => 'ZTEG']))->assertOk();   // dulu 403 padahal menu tampil
        Http::assertSent(fn (HttpRequest $r) => str_contains(urldecode($r->url()), '"$regex":"ZTEG"'));

        // ID berisi %20 dulu ditolak rute → 404
        $this->get(route('acs-servers.show-device', ['deviceId' => self::DEVICE_ID, 'server_id' => $this->server->id]))->assertOk();

        // Server ACS tetap administrator
        $this->get(route('acs-servers.index'))->assertForbidden();
    }

    public function test_reboot_and_config_changes_are_logged_without_secrets(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->post(route('acs-servers.reboot', ['deviceId' => self::DEVICE_ID]), ['server_id' => $this->server->id])->assertRedirect();
        // ID dikirim ke NBI dengan %-encoding yang benar (spasi bukan "+")
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/devices/00259E-ZXHN%2520F670L-ZTEGC1234567/tasks'));
        $this->assertTrue(ActivityLog::where('action', 'acs_rebooted')->where('user_id', $teknisi->id)->exists());

        $this->postJson(route('acs-servers.update-config', ['deviceId' => self::DEVICE_ID]), [
            'server_id' => $this->server->id, 'wifi_ssid' => 'RumahBaru', 'wifi_password' => 'rahasia-wifi-123',
        ])->assertOk();
        $log = ActivityLog::where('action', 'acs_config_updated')->sole();
        $this->assertStringNotContainsString('rahasia-wifi-123', json_encode($log->properties));
        $this->assertStringContainsString('RumahBaru', json_encode($log->properties));
    }

    public function test_api_device_actions_follow_the_same_roles(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
        $this->postJson('/api/v1/admin/genieacs/devices/' . rawurlencode(self::DEVICE_ID) . '/reboot', ['server_id' => $this->server->id])
            ->assertForbidden();   // dulu admin boleh mengubah modem lewat API

        Sanctum::actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));
        $this->postJson('/api/v1/admin/genieacs/devices/' . rawurlencode(self::DEVICE_ID) . '/reboot', ['server_id' => $this->server->id])
            ->assertOk();
        $this->assertTrue(ActivityLog::where('action', 'acs_rebooted')->exists());
    }
}
