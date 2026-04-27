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
}
