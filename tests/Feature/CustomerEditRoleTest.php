<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageChangeRequest;
use App\Models\Radius\RadCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Form edit pelanggan per role: admin semua field; teknisi kontak + lokasi; sales kontak saja.
 * Tautan sales/mitra tidak hilang saat form tidak memuat bagiannya.
 */
class CustomerEditRoleTest extends TestCase
{
    use RefreshDatabase;

    private Package $p10;
    private Package $p20;
    private Customer $c;
    private User $salesRef;
    private User $mitra;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Process::fake();

        $this->p10 = Package::create(['name' => 'Paket 10M', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $this->p20 = Package::create(['name' => 'Paket 20M', 'type' => 'pppoe', 'price' => 250000, 'upload_speed' => '20M', 'download_speed' => '20M', 'is_active' => true]);
        $region = \App\Models\Region::create(['code' => 'BGR', 'name' => 'Bogor']);
        $sto = \App\Models\Sto::create(['code' => 'STO', 'name' => 'STO Bogor', 'region_id' => $region->id]);
        \App\Models\Stb::create(['code' => 'STB', 'name' => 'STB Bogor', 'sto_id' => $sto->id]);
        $region2 = \App\Models\Region::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $sto2 = \App\Models\Sto::create(['code' => 'ST2', 'name' => 'STO Jakarta', 'region_id' => $region2->id]);
        \App\Models\Stb::create(['code' => 'SB2', 'name' => 'STB Jakarta', 'sto_id' => $sto2->id]);

        $this->salesRef = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $this->mitra = User::factory()->create(['role' => 'mitra', 'is_active' => true]);

        $this->c = Customer::create(['customer_code' => '001001009100', 'username' => 'edit@net', 'password' => 'rahasia1', 'name' => 'Pelanggan Edit',
            'package_id' => $this->p10->id, 'phone' => '0811', 'email' => 'a@b.id', 'address' => 'Jl. Lama', 'ktp' => '3201000000000009',
            'region_code' => \App\Models\Region::first()->code, 'sto_code' => \App\Models\Sto::first()->code, 'stb_code' => \App\Models\Stb::first()->code,
            'billing_type' => 'postpaid', 'billing_method' => 'cycle', 'customer_type' => 'personal', 'installation_fee' => 300000,
            'discount_type' => null, 'discount_value' => 0, 'use_tax' => true]);
        $this->c->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true, 'sales_id' => $this->salesRef->id, 'partner_id' => $this->mitra->id]);
        RadCheck::create(['username' => 'edit@net', 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'rahasia1']);
        $this->c = $this->c->fresh();
    }

    /** Payload "jahat": mencoba mengubah semua field terlarang sekaligus */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Nama Diganti', 'username' => 'ganti@net', 'password' => 'baru123', 'ktp' => '3201999999999999',
            'package_id' => $this->p20->id, 'region_code' => \App\Models\Region::latest('id')->first()->code,
            'sto_code' => \App\Models\Sto::latest('id')->first()->code, 'stb_code' => \App\Models\Stb::latest('id')->first()->code,
            'billing_type' => 'prepaid', 'billing_method' => 'renewal', 'customer_type' => 'vip', 'installation_fee' => 0,
            'discount_type' => 'percentage', 'discount_value' => 100, 'is_active' => '0',
            'phone' => '0899', 'email' => 'baru@b.id', 'address' => 'Jl. Baru',
            // use_tax tidak dikirim = mencoba mematikan PPN
        ], $extra);
    }

    private function assertBillingUntouched(): void
    {
        $c = $this->c->fresh();
        $this->assertSame('Pelanggan Edit', $c->name);
        $this->assertSame('edit@net', $c->username);
        $this->assertSame('3201000000000009', $c->ktp);
        $this->assertSame($this->p10->id, $c->package_id, 'paket tidak boleh berubah tanpa pengajuan');
        $this->assertSame('postpaid', $c->billing_type);
        $this->assertSame('cycle', $c->billing_method);
        $this->assertNull($c->discount_type);
        $this->assertEquals(0, (float) $c->discount_value);
        $this->assertTrue((bool) $c->use_tax);
        $this->assertEquals(300000, (float) $c->installation_fee);
        $this->assertSame(Customer::STATUS_ACTIVE, $c->status);
        $this->assertSame('rahasia1', RadCheck::where('username', 'edit@net')->value('value'));
        $this->assertSame($this->salesRef->id, $c->sales_id, 'sales referal dulu terhapus');
        $this->assertSame($this->mitra->id, $c->partner_id, 'tautan mitra dulu terhapus');
    }

    public function test_teknisi_changes_contact_and_location_only(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->put(route('customers.update', $this->c), $this->payload())->assertRedirect();

        $this->assertBillingUntouched();
        $c = $this->c->fresh();
        $this->assertSame('0899', $c->phone);
        $this->assertSame('Jl. Baru', $c->address);
        $this->assertSame(\App\Models\Region::latest('id')->first()->code, $c->region_code);
    }

    public function test_sales_changes_contact_only_and_never_sees_the_password(): void
    {
        $sales = User::factory()->create(['role' => 'sales', 'is_active' => true]);
        $this->actingAs($sales)->get(route('customers.edit', $this->c))->assertOk()->assertDontSee('rahasia1');

        $this->actingAs($sales)->put(route('customers.update', $this->c), $this->payload())->assertRedirect();

        $this->assertBillingUntouched();
        $c = $this->c->fresh();
        $this->assertSame('0899', $c->phone);
        $this->assertSame('baru@b.id', $c->email);
        $this->assertSame(\App\Models\Region::first()->code, $c->region_code, 'sales tidak mengubah wilayah');
    }

    public function test_admin_save_keeps_partner_link_when_partner_module_is_off(): void
    {
        Setting::updateOrCreate(['key' => 'enable_partner_module'], ['value' => '0']);
        \Illuminate\Support\Facades\Cache::forget('app_settings');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Form admin tanpa bagian mitra & sales (modul nonaktif)
        $this->actingAs($admin)->put(route('customers.update', $this->c), $this->payload([
            'name' => 'Nama Admin', 'username' => 'edit@net', 'package_id' => $this->p10->id, 'use_tax' => '1',
            'discount_type' => '', 'discount_value' => 0, 'billing_type' => 'postpaid', 'billing_method' => 'cycle',
            'is_active' => '1', 'password' => 'rahasia1', 'installation_fee' => 300000,
        ]))->assertRedirect();

        $c = $this->c->fresh();
        $this->assertSame('Nama Admin', $c->name);
        $this->assertSame($this->mitra->id, $c->partner_id);
        $this->assertSame($this->salesRef->id, $c->sales_id);
    }

    public function test_teknisi_and_sales_can_request_package_change(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('customers.show', $this->c))->assertOk()->assertSee('Ajukan ganti paket');
        $this->actingAs($teknisi)->post(route('package-requests.store', $this->c), ['to_package_id' => $this->p20->id, 'reason' => 'Minta naik'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, PackageChangeRequest::where('customer_id', $this->c->id)->where('status', 'pending')->count());
        $this->assertSame($this->p10->id, $this->c->fresh()->package_id, 'paket baru berlaku setelah disetujui');
    }

    public function test_api_update_applies_the_same_role_limits(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));
        $this->putJson("/api/v1/admin/customers/{$this->c->id}", [
            'name' => 'X', 'package_id' => $this->p20->id, 'billing_type' => 'prepaid', 'billing_method' => 'renewal',
            'customer_type' => 'vip', 'password' => 'baru1234', 'sales_id' => null, 'installation_fee' => 0,
            'phone' => '0877', 'onu_sn' => 'ZTEGC1234567',
        ])->assertOk()->assertJsonFragment(['ignored_fields' => ['password', 'installation_fee', 'sales_id']]);

        $this->assertBillingUntouched();
        $this->assertSame('0877', $this->c->fresh()->phone);
        $this->assertSame('ZTEGC1234567', $this->c->fresh()->onu_sn);
    }
}
