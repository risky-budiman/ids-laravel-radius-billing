<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.serialization' => 'php']);
        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true
        ]);
        $this->actingAs($this->user);
        
        // Setup initial data
        Package::create([
            'name' => '10Mbps',
            'price' => 150000,
            'speed_limit_down' => 10240,
            'speed_limit_up' => 5120,
            'is_active' => true
        ]);

        $region = \App\Models\Region::create(['code' => 'BGR', 'name' => 'Bogor']);
        $sto = \App\Models\Sto::create(['code' => 'STO', 'name' => 'STO Bogor', 'region_id' => $region->id]);
        $stb = \App\Models\Stb::create(['code' => 'STB', 'name' => 'STB Bogor', 'sto_id' => $sto->id]);

        Odc::create(['id' => 'ODC-BGR-01', 'name' => 'ODC Bogor 1']);
        Odp::create(['id' => 'ODP-BGR-01', 'name' => 'ODP Bogor 1', 'odc_id' => 'ODC-BGR-01']);
    }

    /** @test */
    public function test_it_can_create_a_customer_with_enterprise_fields_and_photos()
    {
        Storage::fake('public');

        // Fetch codes because the model overwrites them with numeric strings
        $region = \App\Models\Region::first();
        $sto = \App\Models\Sto::first();
        $stb = \App\Models\Stb::first();

        $identityPhoto = UploadedFile::fake()->image('ktp.jpg');
        $housePhoto = UploadedFile::fake()->image('house.jpg');

        $response = $this->post(route('customers.store'), [
            'customer_code' => 'BGRSTO001',
            'username' => 'testuser@net.id',
            'password' => 'password123',
            'name' => 'Test Customer Enterprise',
            'region_code' => $region->code,
            'sto_code' => $sto->code,
            'stb_code' => $stb->code,
            'package_id' => 1,
            'billing_type' => 'postpaid',
            'billing_method' => 'cycle',
            'customer_type' => 'corporate',
            'odc_id' => 'ODC-BGR-01',
            'odp_id' => 'ODP-BGR-01',
            'odp_port' => 5,
            'cable_length' => 120,
            'vlan_id' => 100,
            'static_ip' => '10.10.10.50',
            'cpe_brand' => 'FiberHome',
            'cpe_model' => 'HG6243C',
            'identity_photo' => $identityPhoto,
            'house_photo' => $housePhoto,
            'is_active' => true,
            'latitude' => -6.123,
            'longitude' => 106.123,
        ]);

        $response->assertRedirect(route('customers.index'));
        
        $customer = Customer::where('username', 'testuser@net.id')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('corporate', $customer->customer_type);
        $this->assertEquals('ODC-BGR-01', $customer->odc_id);
        $this->assertEquals(5, $customer->odp_port);
        $this->assertNotNull($customer->identity_photo);
        
        Storage::disk('public')->assertExists($customer->identity_photo);
    }

    /** @test */
    public function test_it_can_update_enterprise_fields_and_replace_photos()
    {
        Storage::fake('public');
        
        $customer = Customer::create([
            'customer_code' => 'OLD001',
            'username' => 'olduser',
            'password' => 'oldpassword',
            'name' => 'Old Name',
            'package_id' => 1,
            'region_code' => 'BGR',
            'sto_code' => 'STO',
            'stb_code' => 'STB',
            'billing_type' => 'postpaid',
            'billing_method' => 'cycle',
            'customer_type' => 'personal',
            'identity_photo' => 'old_ktp.jpg'
        ]);

        // Put initial file
        Storage::disk('public')->put('old_ktp.jpg', 'content');

        $newIdentityPhoto = UploadedFile::fake()->image('new_ktp.jpg');

        $region = \App\Models\Region::first();
        $sto = \App\Models\Sto::first();
        $stb = \App\Models\Stb::first();

        $response = $this->put(route('customers.update', $customer), [
            'name' => 'Updated Name',
            'username' => 'olduser', // keep username
            'password' => 'newpassword',
            'package_id' => 1,
            'region_code' => $region->code,
            'sto_code' => $sto->code,
            'stb_code' => $stb->code,
            'billing_type' => 'postpaid',
            'billing_method' => 'cycle',
            'customer_type' => 'vip',
            'identity_photo' => $newIdentityPhoto,
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        
        $customer->refresh();
        $this->assertEquals('vip', $customer->customer_type);
        $this->assertNotEquals('old_ktp.jpg', $customer->identity_photo);
        
        Storage::disk('public')->assertMissing('old_ktp.jpg');
        Storage::disk('public')->assertExists($customer->identity_photo);
    }

    private function editPayload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Pel Uji', 'username' => 'peluji', 'password' => 'rahasia', 'package_id' => 1,
            'region_code' => \App\Models\Region::first()->code, 'sto_code' => \App\Models\Sto::first()->code, 'stb_code' => \App\Models\Stb::first()->code,
            'billing_type' => 'postpaid', 'billing_method' => 'cycle', 'customer_type' => 'personal',
            'installation_fee' => 300000,
        ], $extra);
    }

    public function test_editing_does_not_change_status_of_customer_waiting_for_activation_nor_wipe_coordinates(): void
    {
        $c = Customer::create(['customer_code' => 'W-ACT', 'username' => 'peluji', 'password' => 'x', 'name' => 'Pel', 'package_id' => 1,
            'region_code' => 'BGR', 'sto_code' => 'STO', 'stb_code' => 'STB', 'billing_type' => 'postpaid', 'billing_method' => 'cycle',
            'customer_type' => 'personal', 'is_active' => false, 'status' => Customer::STATUS_WAITING_ACTIVATION,
            'latitude' => -6.21129546, 'longitude' => 106.84375763]);

        // Form edit mengirim is_active=0 ("Terisolir") untuk pelanggan yang belum aktif
        $this->put(route('customers.update', $c), $this->editPayload(['is_active' => '0']))->assertRedirect(route('customers.show', $c));

        $c->refresh();
        $this->assertSame(Customer::STATUS_WAITING_ACTIVATION, $c->status, 'dulu menjadi suspended');
        $this->assertEqualsWithDelta(-6.21129546, (float) $c->latitude, 0.000001, 'dulu dihapus');
        $this->assertEqualsWithDelta(106.84375763, (float) $c->longitude, 0.000001);
        $this->get(route('customers.edit', $c))->assertOk()->assertSee('Menunggu aktivasi')->assertSee('wizard aktivasi');

        // Pelanggan dicabut tidak kembali tertagih lewat form edit
        $c->update(['status' => Customer::STATUS_DISMANTLED]);
        $this->put(route('customers.update', $c), $this->editPayload(['is_active' => '1']));
        $this->assertSame(Customer::STATUS_DISMANTLED, $c->fresh()->status);
        $this->assertFalse((bool) $c->fresh()->is_active);
    }

    public function test_active_customer_can_still_be_isolated_from_edit_form(): void
    {
        $c = Customer::create(['customer_code' => 'A-ACT', 'username' => 'peluji', 'password' => 'x', 'name' => 'Pel', 'package_id' => 1,
            'region_code' => 'BGR', 'sto_code' => 'STO', 'stb_code' => 'STB', 'billing_type' => 'postpaid', 'billing_method' => 'cycle',
            'customer_type' => 'personal', 'is_active' => true, 'status' => Customer::STATUS_ACTIVE]);
        \Illuminate\Support\Facades\Process::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $this->put(route('customers.update', $c), $this->editPayload(['is_active' => '0']));
        $this->assertSame(Customer::STATUS_SUSPENDED, $c->fresh()->status);
    }

    public function test_activation_form_uploads_files_and_shows_errors(): void
    {
        $c = Customer::create(['customer_code' => 'F-ACT', 'username' => 'peluji', 'password' => 'x', 'name' => 'Pel', 'package_id' => 1,
            'region_code' => 'BGR', 'sto_code' => 'STO', 'stb_code' => 'STB', 'billing_type' => 'postpaid', 'billing_method' => 'cycle',
            'customer_type' => 'personal', 'is_active' => false]);
        $c->update(['status' => Customer::STATUS_SUSPENDED]);   // observer menjadikan pelanggan baru "menunggu aktivasi"

        // Tanpa enctype multipart, foto terkirim sebagai nama file -> validasi gagal tanpa pesan
        $this->get(route('customers.activate', $c))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('wizard aktivasi hanya untuk pelanggan baru');

        $this->from(route('customers.activate', $c))->post(route('customers.activate.store', $c), [])
            ->assertRedirect(route('customers.activate', $c));
        $this->get(route('customers.activate', $c))->assertSee('tidak bisa diaktivasi');
    }
}
