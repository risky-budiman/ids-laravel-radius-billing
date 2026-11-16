<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Wizard aktivasi: pilih ONU langsung dari daftar OLT (SNMP), ONU milik pelanggan lain ditandai.
 */
class OnuPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_picker_lists_onus_and_marks_owners(): void
    {
        $olt = Olt::create(['name' => 'OLT HSGQ', 'ip_address' => '10.0.0.30', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true]);
        $port = OltPonPort::create(['olt_id' => $olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        // Hasil scan port (format getOnusOnPort) — tanpa SNMP sungguhan
        Cache::put("olt_port_data_{$port->id}", [
            ['index' => '1.1.1.1', 'sn' => '00:1a:69:43:12:29', 'name' => '20391582228', 'status' => 'online', 'signal' => -15.63, 'distance' => 644],
            ['index' => '1.1.1.2', 'sn' => 'e8:68:19:02:ff:70', 'name' => '20391582226', 'status' => 'online', 'signal' => -14.97, 'distance' => 465],
            ['index' => '1.1.1.3', 'sn' => '68:9f:f0:03:f7:38', 'name' => 'baru', 'status' => 'online', 'signal' => -20.1, 'distance' => 700],
        ], 600);

        $pkg = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $other = Customer::create(['customer_code' => '001001009701', 'username' => 'lain@net', 'name' => 'Pelanggan Lain', 'package_id' => $pkg->id,
            'phone' => '0811', 'onu_sn' => '001A69431229']);   // SN tanpa pemisah tetap dikenali
        $other->update(['olt_id' => $olt->id]);
        $me = Customer::create(['customer_code' => '001001009702', 'username' => 'saya@net', 'name' => 'Pelanggan Ini', 'package_id' => $pkg->id,
            'phone' => '0812', 'onu_sn' => 'E8:68:19:02:FF:70']);
        $me->update(['olt_id' => $olt->id]);

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $res = $this->actingAs($teknisi)->getJson(route('olts.onu-picker', $olt) . '?customer_id=' . $me->id)->assertOk();

        $onus = collect($res->json('onus'))->keyBy('index');
        $this->assertSame(['1/1/1:1', '1/1/1:2', '1/1/1:3'], $onus->keys()->all(), 'posisi dinormalkan ke format 1/1/1:N');
        $this->assertSame('00:1A:69:43:12:29', $onus['1/1/1:1']['sn']);
        $this->assertSame('001001009701', $onus['1/1/1:1']['customer']['code']);
        $this->assertFalse($onus['1/1/1:1']['customer']['self']);
        $this->assertTrue($onus['1/1/1:2']['customer']['self']);
        $this->assertNull($onus['1/1/1:3']['customer']);

        // Admin juga boleh (wizard aktivasi), CS tidak
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))->getJson(route('olts.onu-picker', $olt))->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'cs', 'is_active' => true]))->getJson(route('olts.onu-picker', $olt))->assertForbidden();
    }

    public function test_activation_page_offers_the_picker(): void
    {
        $pkg = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $c = Customer::create(['customer_code' => '001001009703', 'username' => 'aktif@net', 'name' => 'Calon', 'package_id' => $pkg->id, 'phone' => '0813']);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);

        $this->actingAs($teknisi)->get(route('customers.activate', $c))->assertOk()->assertSee('Pilih dari OLT')->assertSee('onu-picker', false);
    }
}
