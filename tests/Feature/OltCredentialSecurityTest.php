<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Olt;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kredensial OLT terenkripsi & tidak tampil; ubah OLT hanya administrator; OLT berpelanggan tidak bisa dihapus.
 */
class OltCredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function olt(): Olt
    {
        return Olt::create([
            'name' => 'OLT Uji', 'ip_address' => '10.0.0.2', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'baca-rahasia', 'snmp_write_community' => 'tulis-rahasia', 'olt_type' => 'ZTE_C320', 'is_active' => true,
        ]);
    }

    public function test_credentials_are_encrypted_at_rest_and_hidden_from_json(): void
    {
        $olt = $this->olt();
        $raw = DB::table('olts')->where('id', $olt->id)->first();

        foreach (['snmp_read_community' => 'baca-rahasia', 'snmp_write_community' => 'tulis-rahasia'] as $col => $plain) {
            $this->assertNotSame($plain, $raw->{$col}, "$col tersimpan teks biasa");
            $this->assertSame($plain, Crypt::decryptString($raw->{$col}));
            $this->assertSame($plain, $olt->fresh()->{$col});
        }
        $json = $olt->fresh()->toJson();
        $this->assertStringNotContainsString('rahasia', $json);
    }

    public function test_edit_form_does_not_reveal_communities_and_blank_keeps_them(): void
    {
        $olt = $this->olt();
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)->get(route('olts.edit', $olt))->assertOk()
            ->assertDontSee('baca-rahasia')->assertDontSee('tulis-rahasia');

        $this->actingAs($admin)->put(route('olts.update', $olt), [
            'name' => 'OLT Uji 2', 'ip_address' => '10.0.0.2', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => '', 'snmp_write_community' => '', 'olt_type' => 'ZTE_C320',
        ])->assertRedirect(route('olts.index'));

        $olt->refresh();
        $this->assertSame('OLT Uji 2', $olt->name);
        $this->assertSame('baca-rahasia', $olt->snmp_read_community);
        $this->assertSame('tulis-rahasia', $olt->snmp_write_community);
        $this->assertTrue(ActivityLog::where('action', 'olt_updated')->exists());
    }

    public function test_olts_are_snmp_only_without_telnet_fields(): void
    {
        $olt = $this->olt();
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        \App\Models\OltPonPort::create(['olt_id' => $olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);   // tanpa deteksi port SNMP
        foreach (['access_method', 'telnet_port', 'username', 'password', 'enable_password'] as $col) {
            $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('olts', $col), "kolom {$col} masih ada");
        }
        foreach ([route('olts.create'), route('olts.edit', $olt), route('olts.show', $olt), route('olts.index')] as $url) {
            $html = strtolower($this->actingAs($admin)->get($url)->assertOk()->getContent());
            $this->assertStringNotContainsString('telnet', $html, $url);
            $this->assertStringNotContainsString('enable_password', $html, $url);
            // Community tidak pernah tampil (dulu community WRITE tampil terbuka di halaman detail OLT)
            $this->assertStringNotContainsString('rahasia', $html, $url);
        }
    }

    public function test_teknisi_can_view_but_not_change_olts(): void
    {
        $olt = $this->olt();
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->get(route('olts.index'))->assertOk()->assertDontSee(route('olts.create'));
        $this->get(route('olts.create'))->assertForbidden();
        $this->post(route('olts.store'), [])->assertForbidden();
        $this->get(route('olts.edit', $olt))->assertForbidden();
        $this->put(route('olts.update', $olt), ['name' => 'x'])->assertForbidden();
        $this->delete(route('olts.destroy', $olt))->assertForbidden();
        $this->assertNotNull($olt->fresh());
    }

    public function test_olt_with_customers_cannot_be_deleted(): void
    {
        $olt = $this->olt();
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $pkg = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $c = Customer::create(['customer_code' => '001001009001', 'username' => 'olt@net', 'password' => 'x12345', 'name' => 'Pelanggan OLT',
            'package_id' => $pkg->id, 'phone' => '0811']);
        $c->update(['olt_id' => $olt->id]);

        $this->actingAs($admin)->delete(route('olts.destroy', $olt))->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($olt->fresh());

        $c->update(['olt_id' => null]);
        $this->actingAs($admin)->delete(route('olts.destroy', $olt))->assertRedirect(route('olts.index'));
        $this->assertNull(Olt::find($olt->id));
        $this->assertTrue(ActivityLog::where('action', 'olt_deleted')->exists());
    }
}
