<?php

namespace Tests\Feature;

use App\Jobs\DeprovisionOnuJob;
use App\Jobs\ProvisionOnuJob;
use App\Jobs\UpdateOnuJob;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Olt;
use App\Models\Package;
use App\Models\User;
use App\Services\Network\OltGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * OLT "SNMP saja": tidak ada perintah telnet; registrasi/hapus/profil ONU dilewati & dicatat, isolir lewat RADIUS saja.
 */
class OltSnmpOnlyModeTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->olt = Olt::create(['name' => 'OLT SNMP', 'ip_address' => '10.0.0.7', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'telnet_port' => 23, 'olt_type' => 'ZTE_C320', 'access_method' => Olt::ACCESS_SNMP, 'is_active' => true]);
        // Gateway yang gagal keras bila SNMP/telnet benar-benar dipakai
        $this->app->bind(OltGateway::class, fn ($app, $p) => new class($p['olt']) extends OltGateway {
            public function serialAt(int $shelf, int $slot, int $port, int $onuId): ?string { throw new \LogicException('tidak boleh dipanggil'); }
        });
    }

    public function test_jobs_skip_telnet_and_log_what_needs_manual_work(): void
    {
        $pkg = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $c = Customer::create(['customer_code' => '001001009300', 'username' => 'snmp@net', 'password' => 'x12345', 'name' => 'Pelanggan SNMP',
            'package_id' => $pkg->id, 'phone' => '0811', 'onu_sn' => 'ZTEGC7654321', 'onu_index' => '1/1/2']);
        $c->update(['olt_id' => $this->olt->id]);

        (new ProvisionOnuJob($c->id))->handle();
        $this->assertTrue(ActivityLog::where('action', 'onu_provision_skipped')->exists());

        (new DeprovisionOnuJob($this->olt->id, '1/1/2:3', 'ZTEGC7654321'))->handle();
        $this->assertTrue(ActivityLog::where('action', 'onu_delete_skipped')->exists());

        (new UpdateOnuJob($this->olt->id, '1/1/2:3', 'suspend', null, 'ZTEGC7654321'))->handle();
        $this->assertFalse(ActivityLog::where('action', 'like', 'onu_action%')->exists(), 'isolir cukup lewat RADIUS');

        (new UpdateOnuJob($this->olt->id, '1/1/2:3', 'update_speed', 'UP-50M', 'ZTEGC7654321'))->handle();
        $this->assertTrue(ActivityLog::where('action', 'onu_action_skipped')->exists());
        $this->assertFalse(ActivityLog::where('action', 'like', '%failed')->exists());
    }

    public function test_gateway_refuses_telnet_and_manual_delete_is_blocked(): void
    {
        $gw = new OltGateway($this->olt);
        $this->assertFalse($gw->suspendOnu(1, 1, 2, 3));
        $this->assertStringContainsString('SNMP saja', $gw->lastWriteError());

        Queue::fake();
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->post(route('olts.manual-delete-onu', $this->olt), ['onu_index' => '1/1/2:3', 'onu_sn' => 'ZTEGC7654321'])
            ->assertSessionHas('error');
        Queue::assertNothingPushed();
        $this->actingAs($admin)->get(route('olts.show', $this->olt))->assertOk()->assertSee('Mode SNMP saja')->assertDontSee('Hapus ONU Manual');
    }

    public function test_access_method_is_saved_and_defaults_to_telnet(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->post(route('olts.store'), ['name' => 'OLT Baru', 'ip_address' => '10.0.0.8', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'ZTE_C320'])->assertRedirect();
        $new = Olt::where('name', 'OLT Baru')->sole();
        $this->assertTrue($new->usesTelnet(), 'bawaan = perilaku lama');

        $this->actingAs($admin)->put(route('olts.update', $new), ['name' => 'OLT Baru', 'ip_address' => '10.0.0.8', 'snmp_port' => 161, 'snmp_version' => 2,
            'olt_type' => 'ZTE_C320', 'access_method' => 'snmp'])->assertRedirect();
        $this->assertFalse($new->fresh()->usesTelnet());
    }
}
