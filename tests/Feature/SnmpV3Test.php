<?php

namespace Tests\Feature;

use App\Models\Olt;
use App\Models\User;
use App\Services\Network\SnmpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dukungan SNMPv3 (user + auth + enkripsi) untuk OLT; perbaikan argumen CLI v1 ("-v1c" tidak valid).
 */
class SnmpV3Test extends TestCase
{
    use RefreshDatabase;

    private function base(array $over = []): array
    {
        return array_merge(['name' => 'OLT V3', 'ip_address' => '10.0.0.20', 'snmp_port' => 161, 'olt_type' => 'HSGQ_EPON'], $over);
    }

    public function test_cli_arguments_per_version(): void
    {
        $this->assertSame('-v1 -c ' . escapeshellarg('pub'), (new SnmpService('10.0.0.1', 'pub', 161, 1))->cliAuthArgs(), 'dulu "-v1c"');
        $this->assertSame('-v2c -c ' . escapeshellarg('pub'), (new SnmpService('10.0.0.1', 'pub', 161, 2))->cliAuthArgs());

        $v3 = (new SnmpService('10.0.0.1'))->withV3('noc', 'authPriv', 'SHA256', 'authpass1', 'AES', 'privpass1');
        $this->assertSame('-v3 -u ' . escapeshellarg('noc') . ' -l ' . escapeshellarg('authPriv')
            . ' -a SHA-256 -A ' . escapeshellarg('authpass1') . ' -x AES -X ' . escapeshellarg('privpass1'), $v3->cliAuthArgs());

        $authOnly = (new SnmpService('10.0.0.1'))->withV3('noc', 'authNoPriv', 'MD5', 'authpass1');
        $this->assertStringNotContainsString(' -x ', $authOnly->cliAuthArgs());
        $this->assertStringNotContainsString('-c ', $authOnly->cliAuthArgs(), 'v3 tidak mengirim community');
    }

    public function test_freedsx_options(): void
    {
        $o = (new SnmpService('10.0.0.1'))->withV3('noc', 'authPriv', 'SHA', 'authpass1', 'AES', 'privpass1')->freeDsxOptions();
        $this->assertSame(['version' => 3, 'user' => 'noc', 'use_auth' => true, 'auth_mech' => 'sha1', 'auth_pwd' => 'authpass1',
            'use_priv' => true, 'priv_mech' => 'aes128', 'priv_pwd' => 'privpass1'],
            array_intersect_key($o, array_flip(['version', 'user', 'use_auth', 'auth_mech', 'auth_pwd', 'use_priv', 'priv_mech', 'priv_pwd'])));
        $this->assertArrayNotHasKey('community', $o);
        $this->assertSame('pub', (new SnmpService('10.0.0.1', 'pub'))->freeDsxOptions()['community']);
    }

    public function test_olt_v3_credentials_are_validated_encrypted_and_kept(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin);

        // authPriv tanpa password → ditolak
        $this->post(route('olts.store'), $this->base(['snmp_version' => 3, 'snmp_v3_user' => 'noc', 'snmp_v3_level' => 'authPriv']))
            ->assertSessionHasErrors(['snmp_v3_auth_password', 'snmp_v3_priv_password']);

        $this->post(route('olts.store'), $this->base(['snmp_version' => 3, 'snmp_v3_user' => 'noc', 'snmp_v3_level' => 'authPriv',
            'snmp_v3_auth_protocol' => 'SHA256', 'snmp_v3_auth_password' => 'authpass1', 'snmp_v3_priv_protocol' => 'AES', 'snmp_v3_priv_password' => 'privpass1',
        ]))->assertRedirect(route('olts.index'));   // community tidak wajib untuk v3

        $olt = Olt::where('name', 'OLT V3')->sole();
        $raw = DB::table('olts')->where('id', $olt->id)->first();
        $this->assertNotSame('authpass1', $raw->snmp_v3_auth_password, 'tersimpan terenkripsi');
        $this->assertStringNotContainsString('privpass1', $olt->toJson());

        $svc = SnmpService::fromOlt($olt);
        $this->assertStringContainsString('-a SHA-256', $svc->cliAuthArgs());
        $this->assertSame($svc->cliAuthArgs(), SnmpService::forWrite($olt)->cliAuthArgs(), 'v3: SET memakai user yang sama');

        // Form ubah tidak menampilkan password; kosong = tetap
        $this->get(route('olts.edit', $olt))->assertOk()->assertDontSee('authpass1')->assertSee('Version 3');
        $this->put(route('olts.update', $olt), $this->base(['snmp_version' => 3, 'snmp_v3_user' => 'noc2', 'snmp_v3_level' => 'authPriv',
            'snmp_v3_auth_protocol' => 'SHA256', 'snmp_v3_priv_protocol' => 'AES']))->assertRedirect(route('olts.index'));
        $olt->refresh();
        $this->assertSame('noc2', $olt->snmp_v3_user);
        $this->assertSame('authpass1', $olt->snmp_v3_auth_password);
        $this->assertSame('privpass1', $olt->snmp_v3_priv_password);
    }

    public function test_v2_write_uses_write_community(): void
    {
        $olt = Olt::create($this->base(['snmp_version' => 2, 'snmp_read_community' => 'baca', 'snmp_write_community' => 'tulis',
            'telnet_port' => 23, 'is_active' => true]));
        $this->assertSame('-v2c -c ' . escapeshellarg('tulis'), SnmpService::forWrite($olt)->cliAuthArgs());
        $this->assertSame('-v2c -c ' . escapeshellarg('baca'), SnmpService::fromOlt($olt)->cliAuthArgs());
    }
}
