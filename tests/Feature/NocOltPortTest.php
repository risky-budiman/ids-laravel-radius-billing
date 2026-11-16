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

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('noc.index'))->assertOk()->assertSee('Tidak terpindai: OLT Mati');
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
        Cache::put('noc_olt_stats_at', now()->subMinutes(2), 300);
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

        // Tanpa cache: halaman langsung tampil dalam mode memuat (tidak menunggu scan SNMP)
        $this->get(route('olts.show-port', [$a, $portA]))->assertOk()->assertSee('if (true)', false);

        // Dengan cache: data tampil dan endpoint data tidak memindai ulang
        $onus = [['index' => '1/1/1:1', 'sn' => 'ZTEGC0000001', 'name' => 'Pelanggan', 'status' => 'online', 'signal' => -20.5]];
        Cache::put("olt_port_data_{$portA->id}", $onus, 600);
        $this->get(route('olts.show-port', [$a, $portA]))->assertOk()->assertSee('ZTEGC0000001')->assertSee('if (false)', false);
        $this->getJson(route('olts.get-port-data', [$a, $portA]))->assertOk()->assertJson(['status' => 'ready', 'onus' => $onus]);
    }
}
