<?php

namespace Tests\Feature;

use App\Models\AcsServer;
use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Support\AcsDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Daftar & detail modem GenieACS: ringkasan, status online, pelanggan tertaut, filter status. */
class AcsDeviceListTest extends TestCase
{
    use RefreshDatabase;

    private function device(string $sn, string $user, int $minutesAgo, $rx = '-1856'): array
    {
        return [
            '_id' => "00259E-HG8546M-{$sn}",
            '_deviceId' => ['_SerialNumber' => $sn, '_ProductClass' => 'HG8546M', '_Manufacturer' => 'Huawei Technologies Co., Ltd'],
            '_lastInform' => now()->subMinutes($minutesAgo)->utc()->toIso8601String(),
            '_tags' => ['odp-01', 'vip', 'pop-a'],
            'InternetGatewayDevice' => ['WANDevice' => [1 => [
                'X_GponInterafceConfig' => ['RXPower' => ['_value' => $rx]],
                'WANConnectionDevice' => [1 => ['WANPPPConnection' => [1 => ['Username' => ['_value' => $user], 'ExternalIPAddress' => ['_value' => '172.18.0.9']]]]],
            ]]],
        ];
    }

    public function test_summary_normalizes_vendor_values(): void
    {
        $s = AcsDevice::summarize($this->device('HWTC1', 'budi@net', 3));
        $this->assertSame(['HWTC1', 'HG8546M', 'Huawei', '172.18.0.9', 'budi@net', -18.56, true],
            [$s['serial'], $s['model'], $s['manufacturer'], $s['ip'], $s['pppoe'], $s['rx'], $s['online']]);
        $this->assertFalse(AcsDevice::summarize($this->device('X', 'x', 120))['online']);
        $this->assertSame(-15.0, AcsDevice::rx('-150'));      // satuan 0,1 dBm
        $this->assertSame(-15.6, AcsDevice::rx('-15.6'));
        $this->assertNull(AcsDevice::rx('0'));
        $this->assertSame('warn', AcsDevice::signalLevel(-25.0));
    }

    public function test_list_links_customers_shows_status_and_filters_offline(): void
    {
        AcsServer::create(['name' => 'Pusat', 'url' => 'http://acs.test', 'is_active' => true]);
        $pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        Customer::create(['customer_code' => '001001000321', 'username' => 'budi@nextlink', 'name' => 'Budi Santoso', 'package_id' => $pkg->id, 'phone' => '08']);

        Http::fake(function (HttpRequest $r) {
            $q = urldecode($r->url());
            if (str_contains($q, 'limit=1') && str_contains($q, '_lastInform')) {
                return Http::response([], 200, ['total' => '1']);           // online
            }
            if (str_contains($q, 'limit=1')) {
                return Http::response([], 200, ['total' => '2']);           // total
            }

            return Http::response([$this->device('HWTC1', 'budi', 2), $this->device('HWTC2', 'asing@net', 300)], 200, ['total' => '2']);
        });
        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));

        $this->get(route('acs-servers.devices'))->assertOk()
            ->assertSee('Modem Pelanggan')->assertSee('Budi Santoso')->assertSee('belum tertaut')
            ->assertSee('-18.56 dBm')->assertSee('Online')->assertSee('Offline')->assertSee('+1');

        $this->get(route('acs-servers.devices', ['status' => 'offline']))->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains(urldecode($r->url()), '"_lastInform":{"$lte"'));
    }
}
