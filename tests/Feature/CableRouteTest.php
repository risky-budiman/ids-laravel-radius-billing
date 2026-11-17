<?php

namespace Tests\Feature;

use App\Models\CableRoute;
use App\Models\Customer;
use App\Models\Odp;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use App\Services\Network\RoadRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Jalur kabel di peta: gambar manual, ikuti jalan (OSRM), hapus, hak akses & pengaturan server routing. */
class CableRouteTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        $pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        $odp = Odp::create(['id' => 'ODP-A1', 'name' => 'ODP A1', 'latitude' => -6.2000, 'longitude' => 106.8000]);
        $this->customer = Customer::create(['customer_code' => '001001000001', 'username' => 'a@net', 'name' => 'Budi', 'package_id' => $pkg->id,
            'phone' => '08', 'latitude' => -6.2010, 'longitude' => 106.8010, 'odp_id' => $odp->id]);
        $this->customer->forceFill(['status' => 'active'])->saveQuietly();
        $this->teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
    }

    public function test_manual_route_is_saved_measured_and_shown_on_map(): void
    {
        $path = [[-6.2000, 106.8000], [-6.2000, 106.8010], [-6.2010, 106.8010]];
        $this->actingAs($this->teknisi)
            ->postJson(route('customers.map.routes.save'), ['node_type' => 'customer', 'node_id' => (string) $this->customer->id, 'path' => $path])
            ->assertOk()->assertJson(['success' => true]);

        $route = CableRoute::first();
        $this->assertSame('manual', $route->mode);
        $this->assertEqualsWithDelta(222, $route->length_m, 3);   // 2 ruas ± 111 m
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'cable_route_saved')->exists());

        $res = $this->get(route('customers.map'))->assertOk();
        $point = collect($res->viewData('points'))->firstWhere('id', $this->customer->id);
        $this->assertSame($path, $point['route']['path']);
        $this->assertSame([-6.2, 106.8], $point['odp']);

        // Simpan ulang = ubah (satu baris per sambungan); hapus = kembali garis lurus
        $this->postJson(route('customers.map.routes.save'), ['node_type' => 'customer', 'node_id' => (string) $this->customer->id, 'path' => [$path[0], $path[2]]])->assertOk();
        $this->assertSame(1, CableRoute::count());
        $this->deleteJson(route('customers.map.routes.destroy'), ['node_type' => 'customer', 'node_id' => (string) $this->customer->id])->assertOk();
        $this->assertSame(0, CableRoute::count());
    }

    public function test_infrastructure_route_and_validation(): void
    {
        $this->actingAs($this->teknisi);
        $this->postJson(route('customers.map.routes.save'), ['node_type' => 'odp', 'node_id' => 'ODP-A1', 'path' => [[-6.19, 106.79], [-6.2, 106.8]]])->assertOk();
        $this->assertTrue(CableRoute::where(['node_type' => 'odp', 'node_id' => 'ODP-A1'])->exists());

        $this->postJson(route('customers.map.routes.save'), ['node_type' => 'odp', 'node_id' => 'TIDAK-ADA', 'path' => [[-6.19, 106.79], [-6.2, 106.8]]])->assertNotFound();
        $this->postJson(route('customers.map.routes.save'), ['node_type' => 'olt', 'node_id' => '1', 'path' => [[-6.19, 106.79], [-6.2, 106.8]]])->assertStatus(422);
        $this->postJson(route('customers.map.routes.save'), ['node_type' => 'odp', 'node_id' => 'ODP-A1', 'path' => [[-6.19, 106.79]]])->assertStatus(422);
        $this->postJson(route('customers.map.routes.save'), ['node_type' => 'odp', 'node_id' => 'ODP-A1', 'path' => [[-95, 106.79], [-6.2, 106.8]]])->assertStatus(422);
    }

    public function test_road_snap_uses_configured_osrm_and_keeps_real_endpoints(): void
    {
        $this->actingAs($this->teknisi);
        // Tanpa server routing: nonaktif, tidak ada permintaan keluar
        Http::fake();
        $this->postJson(route('customers.map.routes.snap'), ['points' => [[-6.2, 106.8], [-6.201, 106.801]]])
            ->assertStatus(422)->assertJsonFragment(['success' => false]);
        Http::assertNothingSent();

        Setting::create(['key' => RoadRouter::URL_KEY, 'value' => 'http://osrm.local:5000', 'type' => 'string']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['osrm.local:5000/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 160.0,
            'geometry' => ['coordinates' => [[106.80005, -6.20005], [106.80005, -6.20100], [106.80095, -6.20100]]]]]])]);

        $res = $this->postJson(route('customers.map.routes.snap'), ['points' => [[-6.2, 106.8], [-6.201, 106.801]]])->assertOk();
        $path = $res->json('path');
        $this->assertSame([-6.2, 106.8], $path[0]);              // ujung asli (tiang ODP) disambung lagi
        $this->assertSame([-6.201, 106.801], end($path));        // dan rumah pelanggan
        $this->assertCount(5, $path);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/route/v1/driving/106.8,-6.2;106.801,-6.201') && str_contains($r->url(), 'geometries=geojson'));

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['osrm.local:5000/*' => Http::response(['code' => 'NoRoute', 'message' => 'Impossible route between points'], 400)]);
        $this->postJson(route('customers.map.routes.snap'), ['points' => [[-6.3, 106.9], [-6.301, 106.901]]])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Server routing tidak menemukan jalan: Impossible route between points']);
    }

    public function test_access_and_routing_settings(): void
    {
        $sales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $this->actingAs($sales)->postJson(route('customers.map.routes.save'), ['node_type' => 'odp', 'node_id' => 'ODP-A1', 'path' => [[-6.19, 106.79], [-6.2, 106.8]]])->assertForbidden();
        $this->actingAs($sales)->get(route('customers.map'))->assertOk()->assertDontSee('Simpan jalur');

        $this->actingAs($this->teknisi)->get(route('customers.map'))->assertOk()->assertSee('Simpan jalur')->assertDontSee('Pengaturan jalur');
        $this->post(route('customers.map.routing-settings'), ['routing_url' => 'http://x', 'routing_profile' => 'driving'])->assertForbidden();

        $root = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($root)->post(route('customers.map.routing-settings'), ['routing_url' => 'javascript:alert(1)', 'routing_profile' => 'driving'])->assertSessionHasErrors('routing_url');
        Http::fake(['10.90.10.20:5000/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 900, 'geometry' => ['coordinates' => [[106.8, -6.1], [106.9, -6.2]]]]]])]);
        $this->post(route('customers.map.routing-settings'), ['routing_url' => 'http://10.90.10.20:5000/', 'routing_profile' => 'foot'])->assertSessionHas('success');
        $this->assertSame('http://10.90.10.20:5000', RoadRouter::baseUrl());
        $this->assertSame('foot', RoadRouter::profile());
        $this->get(route('customers.map'))->assertSee('Pengaturan jalur')->assertSee('aktif');
    }

    public function test_saving_routing_server_tests_it_and_map_explains_missing_odp(): void
    {
        $root = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($root);

        Http::fake(['osrm.local/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Failed to connect to osrm.local port 443 after 10001 ms')]);
        $this->post(route('customers.map.routing-settings'), ['routing_url' => 'https://osrm.local/', 'routing_profile' => 'driving'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Alamat disimpan, tetapi uji gagal') && str_contains($m, 'tidak bisa terhubung ke https://osrm.local'));
        $this->assertSame('https://osrm.local', RoadRouter::baseUrl());

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['osrm.local/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 2300.4, 'geometry' => ['coordinates' => [[106.8272, -6.1754], [106.8230, -6.1950]]]]]])]);
        $this->post(route('customers.map.routing-settings'), ['routing_url' => 'https://osrm.local', 'routing_profile' => 'driving'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2300 m'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '106.8272,-6.1754;106.823,-6.195'));   // titik uji umum, bukan data pelanggan

        // Tanpa ODP berkoordinat: peta menjelaskan kenapa tidak ada garis kabel
        Odp::query()->delete();
        $this->get(route('customers.map'))->assertOk()->assertSee('Belum ada garis kabel untuk digambar.');
        Odp::create(['id' => 'ODP-B', 'name' => 'ODP B', 'latitude' => -6.2, 'longitude' => 106.8]);
        $this->customer->update(['odp_id' => 'ODP-B']);
        $this->get(route('customers.map'))->assertOk()->assertDontSee('Belum ada garis kabel untuk digambar.');
    }
}
