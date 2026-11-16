<?php

namespace Tests\Feature;

use App\Jobs\SendCustomWhatsappMessageJob;
use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\Olt;
use App\Models\Package;
use App\Models\Setting;
use App\Services\Network\OltGateway;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * noc:monitor mengirim peringatan (dulu NocAlertService tidak dipanggil di mana pun) dan jadwalnya
 * mengikuti pengaturan (bawaan nonaktif).
 */
class NocAlertTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private object $gw;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake();
        Setting::updateOrCreate(['key' => 'whatsapp_noc_number'], ['value' => '628111']);
        Cache::forget('app_settings');

        $this->olt = Olt::create(['name' => 'OLT Pusat', 'ip_address' => '10.0.0.5', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'telnet_port' => 23, 'olt_type' => 'ZTE_C320', 'is_active' => true]);

        \App\Models\OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);

        // Gateway palsu: ONU per port (jalur yang sama dengan halaman port OLT), hasil per SN diatur dari tes
        $this->gw = new class($this->olt) extends OltGateway {
            public array $result = [];
            public bool $down = false;
            public function getOnusOnPort(int $shelf, int $slot, int $port): array
            {
                if ($this->down) throw new \RuntimeException('timeout');
                $rows = [];
                foreach ($this->result as $sn => $r) {
                    $rows[] = ['index' => '1.1.1.' . (count($rows) + 1), 'sn' => strtolower($sn), 'status' => $r['status'] ?? 'online',
                        'signal' => $r['rx_power'] ?? 'N/A'];
                }
                return $rows;
            }
            public function getOltStatus(): array { return ['status' => $this->down ? 'offline' : 'online']; }
        };
        $this->app->bind(OltGateway::class, fn () => $this->gw);
    }

    private function customer(string $sn, string $status = Customer::STATUS_ACTIVE): Customer
    {
        $pkg = Package::firstOrCreate(['name' => 'P10'], ['type' => 'pppoe', 'price' => 100000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $c = Customer::create(['customer_code' => '00100100' . random_int(1000, 9999), 'username' => strtolower($sn) . '@net', 'password' => 'x12345',
            'name' => "Pelanggan {$sn}", 'package_id' => $pkg->id, 'phone' => '0811', 'olt_id' => $this->olt->id, 'onu_sn' => $sn, 'onu_index' => '1/1/1:1']);
        $c->update(['status' => $status, 'is_active' => $status === Customer::STATUS_ACTIVE, 'olt_id' => $this->olt->id]);
        CustomerSignalCache::create(['customer_id' => $c->id, 'onu_index' => '1/1/1:1', 'status' => 'online', 'rx_power' => -20, 'last_polled_at' => now()]);

        return $c;
    }

    private function sentMessages(): array
    {
        $msgs = [];
        Queue::assertPushed(SendCustomWhatsappMessageJob::class, function ($job) use (&$msgs) {
            $msgs[] = $job->message;
            return true;
        });

        return $msgs;
    }

    public function test_los_and_critical_signal_alert_once_per_window(): void
    {
        $this->customer('ZTEGAAAA0001');
        $this->customer('ZTEGAAAA0002');
        $this->customer('ZTEGAAAA0003', Customer::STATUS_SUSPENDED);   // terisolir: bukan gangguan
        $this->gw->result = [
            'ZTEGAAAA0001' => ['status' => 'los'],
            'ZTEGAAAA0002' => ['status' => 'online', 'rx_power' => -29.4],
            'ZTEGAAAA0003' => ['status' => 'los'],
        ];

        $this->artisan('noc:monitor')->assertSuccessful();

        Queue::assertPushed(SendCustomWhatsappMessageJob::class, 1);   // satu ringkasan per OLT
        $msg = $this->sentMessages()[0];
        $this->assertStringContainsString('Pelanggan ZTEGAAAA0001 (los)', $msg);
        $this->assertStringContainsString('-29.4 dBm', $msg);
        $this->assertStringNotContainsString('ZTEGAAAA0003', $msg);

        // Polling berikutnya dalam 6 jam tidak mengulang peringatan
        $this->artisan('noc:monitor')->assertSuccessful();
        Queue::assertPushed(SendCustomWhatsappMessageJob::class, 1);
    }

    public function test_olt_down_is_alerted_once_and_recovery_is_announced(): void
    {
        $this->gw->down = true;
        $this->artisan('noc:monitor');
        $this->artisan('noc:monitor');
        Queue::assertPushed(SendCustomWhatsappMessageJob::class, 1);
        $this->assertStringContainsString('TIDAK MERESPONS', $this->sentMessages()[0]);

        $this->gw->down = false;
        $this->artisan('noc:monitor');
        Queue::assertPushed(SendCustomWhatsappMessageJob::class, 2);
        $this->assertStringContainsString('kembali online', $this->sentMessages()[1]);
    }

    public function test_schedule_follows_the_interval_setting(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'noc:monitor'));
        $this->assertNotNull($event);

        $this->travelTo(now()->setTime(10, 15));
        $this->assertFalse($event->filtersPass($this->app), 'bawaan nonaktif');

        Setting::updateOrCreate(['key' => 'noc_monitor_interval'], ['value' => '15']);
        Cache::forget('app_settings');
        $this->assertTrue($event->filtersPass($this->app));

        $this->travelTo(now()->setTime(10, 20));
        $this->assertFalse($event->filtersPass($this->app), '10:20 bukan kelipatan 15 menit');
    }
}
