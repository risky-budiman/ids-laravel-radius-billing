<?php

namespace Tests\Feature;

use App\Models\Olt;
use App\Models\User;
use App\Services\Network\HsgqWebClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * API web OLT HSGQ: login (md5 "user:pass" + base64), token X-Token, baca daftar izin ONU; kredensial terenkripsi.
 */
class HsgqWebClientTest extends TestCase
{
    use RefreshDatabase;

    private function olt(array $over = []): Olt
    {
        return Olt::create(array_merge(['name' => 'HSGQ', 'ip_address' => '10.0.0.40', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true,
            'web_username' => 'admin', 'web_password' => 'rahasia-web', 'web_port' => 80], $over));
    }

    public function test_login_and_allow_list(): void
    {
        Http::fake([
            '10.0.0.40/userlogin*' => Http::response(['code' => 1, 'message' => 'ok'], 200, ['X-Token' => 'tok123']),
            '10.0.0.40/onu_allow_list*' => Http::response(['code' => 1, 'data' => [['onu_id' => 1, 'macaddr' => '00:1a:69:43:12:29']]]),
        ]);

        $list = (new HsgqWebClient($this->olt()))->allowList(1);

        $this->assertSame('00:1a:69:43:12:29', $list[0]['macaddr']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'userlogin?form=login')
            && $r['param']['key'] === md5('admin:rahasia-web')
            && $r['param']['value'] === base64_encode('rahasia-web')
            && $r['method'] === 'set');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'onu_allow_list?port_id=1') && $r->header('X-Token') === ['tok123']);
    }

    public function test_rejected_login_and_missing_credentials_give_clear_reasons(): void
    {
        Http::fake(['10.0.0.40/userlogin*' => Http::response(['code' => 2, 'message' => 'password error'])]);
        try {
            (new HsgqWebClient($this->olt()))->login();
            $this->fail('harus gagal');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('password error', $e->getMessage());
        }

        $this->expectExceptionMessage('belum diisi');
        (new HsgqWebClient($this->olt(['web_username' => null, 'web_password' => null])))->login();
    }

    public function test_web_password_encrypted_kept_and_test_button_for_administrator(): void
    {
        Http::fake([
            '10.0.0.40/userlogin*' => Http::response(['code' => 1], 200, ['X-Token' => 't']),
            '10.0.0.40/board_info*' => Http::response(['code' => 1, 'data' => ['product_name' => 'HSGQ-E04ID', 'fw_ver' => 'V3.3.3']]),
        ]);
        $olt = $this->olt();
        $this->assertNotSame('rahasia-web', DB::table('olts')->where('id', $olt->id)->value('web_password'));
        $this->assertStringNotContainsString('rahasia-web', $olt->toJson());

        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->get(route('olts.edit', $olt))->assertOk()->assertSee('Tes login web')->assertDontSee('rahasia-web');
        $this->actingAs($admin)->put(route('olts.update', $olt), ['name' => 'HSGQ', 'ip_address' => '10.0.0.40', 'snmp_port' => 161,
            'snmp_version' => 2, 'olt_type' => 'HSGQ_EPON', 'web_username' => 'admin', 'web_password' => ''])->assertRedirect();
        $this->assertSame('rahasia-web', $olt->fresh()->web_password, 'kosong = tetap');

        $this->actingAs($admin)->postJson(route('olts.test-web-login', $olt))->assertOk()
            ->assertJson(['success' => true])->assertJsonFragment(['message' => 'Login web OLT berhasil — HSGQ-E04ID (firmware V3.3.3).']);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->postJson(route('olts.test-web-login', $olt))->assertForbidden();
    }
}
