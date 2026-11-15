<?php

namespace Tests\Feature;

use App\Jobs\DeprovisionOnuJob;
use App\Jobs\UpdateOnuJob;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Olt;
use App\Models\Package;
use App\Models\User;
use App\Services\Network\OltGateway;
use App\Services\Network\ZteOids;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Aksi tulis ke ONU hanya mengenai ONU yang benar (SN dicocokkan), dan ganti kecepatan memakai profil T-CONT.
 */
class OnuSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private object $gw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->olt = Olt::create(['name' => 'OLT-1', 'ip_address' => '10.0.0.2', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'ZTE C320', 'is_active' => true]);

        // OLT tiruan: posisi 1/1/7:5 berisi SN tertentu; mencatat perintah tulis yang dijalankan
        $this->gw = new class($this->olt) extends OltGateway {
            public array $onus = ['1/1/7:5' => 'ZTEGC0000001'];
            public array $calls = [];

            public function serialAt(int $shelf, int $slot, int $port, int $onuId): ?string
            {
                return $this->onus["{$shelf}/{$slot}/{$port}:{$onuId}"] ?? null;
            }

            public function deprovisionOnu(int $shelf, int $slot, int $port, int $onuId): bool
            {
                $this->calls[] = "delete {$shelf}/{$slot}/{$port}:{$onuId}";

                return true;
            }

            public function suspendOnu(int $shelf, int $slot, int $port, int $onuId): bool
            {
                $this->calls[] = "suspend {$shelf}/{$slot}/{$port}:{$onuId}";

                return true;
            }

            public function updateOnuProfile(int $shelf, int $slot, int $port, int $onuId, $package): bool
            {
                $this->calls[] = "profile {$shelf}/{$slot}/{$port}:{$onuId} {$package}";

                return true;
            }
        };
        $this->app->bind(OltGateway::class, fn () => $this->gw);
    }

    public function test_index_formats_are_all_understood(): void
    {
        $expected = ['shelf' => 1, 'slot' => 1, 'port' => 7, 'onu_id' => 5];
        foreach (['.1.1.7.5', '1.1.7.5', '1/1/7:5', 'gpon-onu_1/1/7:5'] as $idx) {
            $this->assertSame($expected, ZteOids::parseOnuPosition($idx), $idx);
        }
        foreach (['', '1/1/7', 'abc', '1/1/7:0', '1;reboot'] as $bad) {
            $this->assertNull(ZteOids::parseOnuPosition($bad), $bad);
        }
    }

    public function test_onu_is_deleted_only_when_serial_matches(): void
    {
        // Indeks format NOC (dulu selalu "format tidak valid")
        (new DeprovisionOnuJob($this->olt->id, '1/1/7:5', 'ztegc0000001'))->handle();
        $this->assertSame(['delete 1/1/7:5'], $this->gw->calls);
        $this->assertTrue(ActivityLog::where('action', 'onu_deleted')->exists());

        // Posisi sudah dipakai ONU pelanggan lain -> dibatalkan
        $this->gw->calls = [];
        $this->gw->onus['1/1/7:5'] = 'ZTEGC0000999';
        (new DeprovisionOnuJob($this->olt->id, '.1.1.7.5', 'ZTEGC0000001'))->handle();
        $this->assertSame([], $this->gw->calls);
        $this->assertStringContainsString('ZTEGC0000999', ActivityLog::where('action', 'onu_delete_aborted')->latest('id')->value('description'));

        // Tanpa SN / indeks salah / OLT HSGQ -> tidak menghapus apa pun
        (new DeprovisionOnuJob($this->olt->id, '1/1/7:5', null))->handle();
        (new DeprovisionOnuJob($this->olt->id, 'salah', 'ZTEGC0000999'))->handle();
        $this->olt->update(['olt_type' => 'HSGQ G08']);
        (new DeprovisionOnuJob($this->olt->id, '1/1/7:5', 'ZTEGC0000999'))->handle();
        $this->assertSame([], $this->gw->calls);
    }

    public function test_speed_change_uses_tcont_profile_and_checks_serial(): void
    {
        (new UpdateOnuJob($this->olt->id, '1/1/7:5', 'update_speed', 'UP-20M', 'ZTEGC0000001'))->handle();
        $this->assertSame(['profile 1/1/7:5 UP-20M'], $this->gw->calls);

        // Paket tanpa profil: dilewati (kecepatan diatur RADIUS)
        $this->gw->calls = [];
        (new UpdateOnuJob($this->olt->id, '1/1/7:5', 'update_speed', null, 'ZTEGC0000001'))->handle();
        $this->assertSame([], $this->gw->calls);

        // SN beda -> isolir ONU dibatalkan
        (new UpdateOnuJob($this->olt->id, '1/1/7:5', 'suspend', null, 'ZTEGC0000777'))->handle();
        $this->assertSame([], $this->gw->calls);
        $this->assertTrue(ActivityLog::where('action', 'onu_action_aborted')->exists());
    }

    public function test_package_change_dispatches_profile_name_not_bandwidth(): void
    {
        Queue::fake();
        Process::fake();
        $p1 = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M']);
        $p2 = Package::create(['name' => 'P20', 'type' => 'pppoe', 'price' => 200000, 'upload_speed' => '20M', 'download_speed' => '20M', 'olt_tcont_profile' => 'UP-20M']);
        $c = Customer::create(['customer_code' => 'C-ONU', 'username' => 'c-onu', 'name' => 'Pel', 'package_id' => $p1->id,
            'olt_id' => $this->olt->id, 'onu_index' => '1/1/7:5', 'onu_sn' => 'ZTEGC0000001']);
        $c->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);

        app(\App\Services\PackageChangeService::class)->apply($c->fresh(), $p2);

        Queue::assertPushed(UpdateOnuJob::class, fn ($j) => $j->action === 'update_speed' && $j->profile === 'UP-20M' && $j->sn === 'ZTEGC0000001');
    }

    public function test_manual_delete_requires_serial(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->post(route('olts.manual-delete-onu', $this->olt), ['onu_index' => '1/1/7:5'])->assertSessionHasErrors('onu_sn');
        $this->post(route('olts.manual-delete-onu', $this->olt), ['onu_index' => '1/1/7', 'onu_sn' => 'ZTEGC0000001'])->assertSessionHas('error');
        $this->post(route('olts.manual-delete-onu', $this->olt), ['onu_index' => '1/1/7:5', 'onu_sn' => 'ztegc0000001'])->assertSessionHas('success');
        Queue::assertPushed(DeprovisionOnuJob::class, fn ($j) => $j->sn === 'ZTEGC0000001' && $j->requestedBy === $admin->id);
    }
}
