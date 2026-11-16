<?php

namespace Tests\Feature;

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\User;
use App\Services\Network\NocSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Dasbor NOC tidak memindai OLT offline & memakai cache; halaman port tampil tanpa menunggu scan,
 * port milik OLT lain tidak bisa dibuka.
 */
class NocOltPortTest extends TestCase
{
    use RefreshDatabase;

    private function olt(string $name): Olt
    {
        return Olt::create(['name' => $name, 'ip_address' => '10.255.255.1', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'telnet_port' => 23, 'olt_type' => 'ZTE_C320', 'is_active' => true]);
    }

    public function test_offline_olts_are_not_scanned_and_reported_as_unscanned(): void
    {
        $olt = $this->olt('OLT Mati');
        Cache::put('noc_olt_stats', [$olt->id => ['status' => 'offline', 'cpu' => 0, 'uptime' => 'N/A', 'temp' => 0]], 300);

        $t = microtime(true);
        $res = NocSnapshot::unconfigured(Olt::all());
        $this->assertLessThan(2, microtime(true) - $t, 'OLT offline tidak boleh ditunggu sampai timeout');
        $this->assertSame(0, $res['count']);
        $this->assertSame(['OLT Mati'], $res['skipped']);
        $this->assertNotNull(Cache::get('noc_unconfigured'), 'hasil scan di-cache');

        // Dasbor NOC tidak memindai SNMP saat dibuka (pindai ONU dari halaman Pencarian ONU)
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('noc.index'))->assertOk()->assertSee('OLT Mati')->assertSee('Tidak merespons');
    }

    public function test_olt_list_shows_cached_health_without_polling(): void
    {
        $up = $this->olt('OLT Hidup');
        $down = $this->olt('OLT Mati');
        $new = $this->olt('OLT Baru');
        Cache::put('noc_olt_stats', [
            $up->id => ['status' => 'online', 'cpu' => 91, 'temp' => 40, 'uptime' => '3d 4h'],
            $down->id => ['status' => 'offline', 'cpu' => 0, 'temp' => 0, 'uptime' => 'N/A'],
        ], 300);
        Cache::put('noc_olt_stats_at', now()->subMinutes(2)->getTimestamp(), 300);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);

        $t = microtime(true);
        $this->actingAs($teknisi)->get(route('olts.index'))->assertOk()
            ->assertSee('1 / 3')                       // OLT online dari cache
            ->assertSee('91%')->assertSee('3d 4h')
            ->assertSee('Tidak merespons')->assertSee('Belum dicek')
            ->assertSee('ZTE ZXA10 C320')->assertDontSee('ZTE_C320')
            ->assertSee('Dicek 2 menit yang lalu')
            ->assertDontSee(route('olts.edit', $up));   // teknisi tidak bisa mengubah
        $this->assertLessThan(3, microtime(true) - $t, 'halaman daftar tidak boleh memindai SNMP');
    }

    public function test_olt_list_works_with_the_database_cache_store(): void
    {
        // Server memakai CACHE_STORE=database + serializable_classes=false: objek (Carbon) di cache rusak saat
        // dibaca kembali. Dulu waktu pengecekan disimpan sebagai Carbon -> halaman Data OLT error 500.
        config(['cache.default' => 'database']);
        $olt = $this->olt('OLT DB');
        Cache::put('noc_olt_stats', [$olt->id => ['status' => 'online', 'cpu' => 5, 'temp' => 30, 'uptime' => '1d']], 300);
        Cache::put('noc_olt_stats_at', now()->getTimestamp(), 300);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);

        $this->actingAs($teknisi)->get(route('olts.index'))->assertOk()->assertSee('Dicek');

        // Nilai lama yang rusak (objek) dibaca sebagai "belum dicek", bukan error
        Cache::put('noc_olt_stats_at', now(), 300);
        $this->actingAs($teknisi)->get(route('olts.index'))->assertOk();
    }

    public function test_olt_detail_page_is_lean_and_shows_device_info(): void
    {
        $olt = Olt::create(['name' => 'HSGQ Detail', 'ip_address' => '10.0.0.60', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true,
            'web_username' => 'admin', 'web_password' => 'rahasia-web']);
        OltPonPort::create(['olt_id' => $olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        Cache::put('noc_olt_stats', [$olt->id => ['status' => 'online', 'cpu' => 9, 'temp' => 35, 'uptime' => '40d 21h']], 300);
        \Illuminate\Support\Facades\Http::fake([
            '10.0.0.60/userlogin*' => \Illuminate\Support\Facades\Http::response(['code' => 1], 200, ['X-Token' => 't']),
            '10.0.0.60/board_info*' => \Illuminate\Support\Facades\Http::response(['code' => 1, 'data' => ['product_name' => 'HSGQ-E04ID', 'fw_ver' => 'IR_V3.3.3ID', 'sn' => 'H4EG0001']]),
        ]);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);

        $this->actingAs($teknisi)->get(route('olts.show', $olt))->assertOk()
            ->assertSee('40d 21h')->assertSee('9%')->assertSee('35°C')
            ->assertSee('HSGQ-E04ID')->assertSee('IR_V3.3.3ID')->assertSee('H4EG0001')
            ->assertSee('Port PON')->assertSee('Pindai semua port')
            // Panel lama yang tidak efisien sudah dibuang
            ->assertDontSee('Buat Port PON')->assertDontSee('Scan Unconfigured')->assertDontSee('ACTIVE &amp; OPERATIONAL', false)
            ->assertDontSee('rahasia-web');
    }

    public function test_port_page_uses_cache_and_rejects_ports_of_other_olts(): void
    {
        $a = $this->olt('OLT A');
        $b = $this->olt('OLT B');
        $portA = OltPonPort::create(['olt_id' => $a->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        $portB = OltPonPort::create(['olt_id' => $b->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        // Port milik OLT lain lewat URL OLT A → 404 (dulu terbuka)
        $this->get(route('olts.show-port', [$a, $portB]))->assertNotFound();
        $this->getJson(route('olts.get-port-data', [$a, $portB]))->assertNotFound();

        // Halaman langsung tampil; daftar ONU dimuat di latar lewat endpoint data (tidak menunggu scan SNMP)
        $this->get(route('olts.show-port', [$a, $portA]))->assertOk()->assertSee('PON 1/1')->assertSee('onuPortManager()', false);

        // Dengan cache: endpoint data tidak memindai ulang; ONU ditautkan ke pelanggan (SN tanpa pemisah pun cocok)
        $pkg = \App\Models\Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $c = \App\Models\Customer::create(['customer_code' => '001001009801', 'username' => 'port@net', 'name' => 'Pelanggan Port',
            'package_id' => $pkg->id, 'phone' => '0811', 'onu_sn' => 'ztegc0000001']);
        $c->update(['olt_id' => $a->id]);
        Cache::put("olt_port_data_{$portA->id}", [
            ['index' => '1/1/1:1', 'sn' => 'ZTEGC0000001', 'name' => 'Pelanggan', 'status' => 'online', 'signal' => -20.5, 'description' => '-'],
            ['index' => '1/1/1:2', 'sn' => 'ZTEGC0000002', 'name' => 'Lain', 'status' => 'offline', 'signal' => 'N/A'],
        ], 600);
        $res = $this->getJson(route('olts.get-port-data', [$a, $portA]))->assertOk()->assertJson(['status' => 'ready']);
        $this->assertSame('001001009801', $res->json('onus.0.customer.code'));
        $this->assertNull($res->json('onus.1.customer'));
        $this->assertSame('', $res->json('onus.0.description'), 'tanda "-" tidak ditampilkan sebagai deskripsi');
        $this->assertTrue($res->json('capabilities.reboot'), 'ZTE: reboot lewat SNMP');
    }

    public function test_hsgq_descriptions_from_web_api_and_reboot_not_offered(): void
    {
        $olt = Olt::create(['name' => 'HSGQ', 'ip_address' => '10.0.0.50', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true,
            'web_username' => 'admin', 'web_password' => 'rahasia-web']);
        $port = OltPonPort::create(['olt_id' => $olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        Cache::put("olt_port_data_{$port->id}", [
            ['index' => '1.1.1.1', 'onu_id' => 1, 'sn' => '00:1a:69:43:12:29', 'name' => '20391582228', 'status' => 'online', 'signal' => -15.6, 'description' => '-'],
        ], 600);
        \Illuminate\Support\Facades\Http::fake([
            '10.0.0.50/userlogin*' => \Illuminate\Support\Facades\Http::response(['code' => 1], 200, ['X-Token' => 't']),
            // Bentuk data dari HSGQ-E04ID asli (IR_V3.3.3ID)
            '10.0.0.50/onu_allow_list*' => \Illuminate\Support\Facades\Http::response(['code' => 1, 'data' => [
                ['port_id' => 1, 'onu_id' => 1, 'onu_name' => '20391582228', 'onu_desc' => 'Ilhamsyah Pu', 'macaddr' => '00:1a:69:43:12:29',
                    'status' => 'Offline', 'vendor' => 'YOTU', 'onu_type' => '4fe', 'register_time' => '2026/11/05 00:09:19',
                    'last_down_time' => '2026/11/16 10:00:00', 'last_down_reason' => 'Dying gasp'],
            ]]),
            '10.0.0.50/ponmgmt*' => \Illuminate\Support\Facades\Http::response(['code' => 1, 'data' => [
                ['port_id' => 1, 'onu_id' => 1, 'macaddr' => '00:1a:69:43:12:29', 'work_temprature' => ' 44 °C', 'work_voltage' => '3.29 V',
                    'transmit_bias' => ' 12 mA', 'transmit_power' => '1.7629 dBm', 'receive_power' => '-15.6543 dBm'],
            ]]),
        ]);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $res = $this->getJson(route('olts.get-port-data', [$olt, $port]))->assertOk();
        $this->assertSame('Ilhamsyah Pu', $res->json('onus.0.description'));
        $this->assertSame('offline', $res->json('onus.0.status'), 'status dari web OLT menggantikan tebakan SNMP');
        $this->assertSame('Dying gasp', $res->json('onus.0.last_down_reason'));
        $this->assertSame('YOTU 4fe', $res->json('onus.0.type'));
        $this->assertEquals(1.7629, $res->json('onus.0.tx_power'));
        $this->assertEquals(44, $res->json('onus.0.temperature'));
        // Dengan akses web, aksi ONU HSGQ tersedia lewat API web OLT (lihat HsgqOnuWriteTest)
        $this->assertTrue($res->json('capabilities.reboot'));
        $this->assertTrue($res->json('capabilities.allow'));
        $this->assertTrue($res->json('capabilities.edit'));
    }

    public function test_zte_reboot_checks_serial_first_and_is_logged(): void
    {
        $olt = $this->olt('OLT ZTE');
        $port = OltPonPort::create(['olt_id' => $olt->id, 'slot' => 1, 'pon_port' => 7, 'status' => 'active']);
        $gw = new class($olt) extends \App\Services\Network\OltGateway {
            public array $rebooted = [];
            public function serialAt(int $shelf, int $slot, int $port, int $onuId): ?string { return $onuId === 5 ? 'ZTEGC0000001' : 'ZTEGC0000999'; }
            public function rebootOnu(string $index): bool { $this->rebooted[] = $index; return true; }
        };
        $this->app->bind(\App\Services\Network\OltGateway::class, fn () => $gw);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->postJson(route('olts.onus.reboot', [$olt, $port]), ['index' => '1/1/7:6', 'sn' => 'ZTEGC0000001'])->assertStatus(422);
        $this->assertSame([], $gw->rebooted, 'SN beda: ONU pelanggan lain tidak di-reboot');

        $this->postJson(route('olts.onus.reboot', [$olt, $port]), ['index' => '1/1/7:5', 'sn' => 'ZTEGC0000001'])->assertOk();
        $this->assertSame(['1.1.7.5'], $gw->rebooted);
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'onu_rebooted')->exists());
    }
}
