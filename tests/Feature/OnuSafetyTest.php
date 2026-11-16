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

        // Gateway tiruan: sistem hanya SNMP — job ONU tidak boleh mengirim perintah apa pun ke OLT
        $this->gw = new class($this->olt) extends OltGateway {
            public array $calls = [];

            public function serialAt(int $shelf, int $slot, int $port, int $onuId): ?string
            {
                $this->calls[] = 'serialAt';

                return null;
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

    public function test_onu_jobs_only_record_manual_work(): void
    {
        $pkg = Package::create(['name' => 'P20', 'type' => 'pppoe', 'price' => 200000, 'upload_speed' => '20M', 'download_speed' => '20M', 'olt_tcont_profile' => 'UP-20M']);
        $c = Customer::create(['customer_code' => '001001009500', 'username' => 'manual@net', 'name' => 'Pel Manual', 'package_id' => $pkg->id,
            'olt_id' => $this->olt->id, 'onu_index' => '1/1/7', 'onu_sn' => 'ZTEGC0000001']);
        $c->update(['olt_id' => $this->olt->id]);

        (new \App\Jobs\ProvisionOnuJob($c->id))->handle();
        $this->assertStringContainsString('SN ZTEGC0000001', ActivityLog::where('action', 'onu_provision_manual')->value('description'));
        $this->assertStringContainsString('UP-20M', ActivityLog::where('action', 'onu_provision_manual')->value('description'));

        (new DeprovisionOnuJob($this->olt->id, '1/1/7:5', 'ZTEGC0000001'))->handle();
        $this->assertStringContainsString('posisi 1/1/7:5', ActivityLog::where('action', 'onu_delete_manual')->value('description'));

        (new UpdateOnuJob($this->olt->id, '1/1/7:5', 'update_speed', 'UP-20M', 'ZTEGC0000001'))->handle();
        $this->assertTrue(ActivityLog::where('action', 'onu_profile_manual')->exists());

        // Isolir/buka cukup lewat RADIUS; paket tanpa profil tidak perlu apa-apa
        $before = ActivityLog::count();
        (new UpdateOnuJob($this->olt->id, '1/1/7:5', 'suspend', null, 'ZTEGC0000001'))->handle();
        (new UpdateOnuJob($this->olt->id, '1/1/7:5', 'update_speed', null, 'ZTEGC0000001'))->handle();
        $this->assertSame($before, ActivityLog::count());

        $this->assertSame([], $this->gw->calls, 'tidak ada perintah ke OLT');
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
}
