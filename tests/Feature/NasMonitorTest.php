<?php

namespace Tests\Feature;

use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\User;
use App\Services\Network\NasMonitor;
use App\Services\Network\NocAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pemantauan router/NAS: mati setelah 2x gagal berturut-turut, sekali sampai pulih; status di halaman Router. */
class NasMonitorTest extends TestCase
{
    use RefreshDatabase;

    private Nas $nas;
    public array $sent = [];
    public bool $up = true;
    public ?string $via = 'ping';

    protected function setUp(): void
    {
        parent::setUp();
        $this->nas = Nas::create(['nasname' => '10.10.10.1', 'shortname' => 'MT-Pusat', 'type' => 'other', 'secret' => 's']);
        Nas::create(['nasname' => '0.0.0.0/0', 'shortname' => 'semua', 'type' => 'other', 'secret' => 's']);   // rentang: dilewati
        RadAcct::insert([['username' => 'a@net', 'nasipaddress' => '10.10.10.1', 'acctstarttime' => now(), 'acctstoptime' => null,
            'acctsessionid' => 'x1', 'acctuniqueid' => 'u1']]);

        $test = $this;
        $this->app->instance(NasMonitor::class, new class($test) extends NasMonitor {
            public function __construct(private $t) {}
            public function probe(string $ip): array { $this->t->probed[] = $ip; return ['up' => $this->t->up, 'via' => $this->t->up ? $this->t->via : null]; }
        });
        $this->app->instance(NocAlertService::class, new class($test) extends NocAlertService {
            public function __construct(private $t) {}
            public function sendAlert(string $message): void { $this->t->sent[] = $message; }
        });
    }

    public array $probed = [];

    public function test_down_after_two_failures_once_then_recovery(): void
    {
        $this->artisan('noc:monitor')->expectsOutputToContain('Router MT-Pusat (10.10.10.1): online via ping, 1 sesi aktif');
        $this->assertSame(['10.10.10.1'], $this->probed);   // 0.0.0.0/0 tidak dicek
        $this->assertSame([], $this->sent);

        $this->up = false;
        $this->artisan('noc:monitor');
        $this->assertSame([], $this->sent);   // satu kali gagal: belum peringatan

        $this->artisan('noc:monitor');
        $this->assertCount(1, $this->sent);
        $this->assertStringContainsString('Router MT-Pusat (10.10.10.1) TIDAK MERESPONS', $this->sent[0]);
        $this->assertStringContainsString('Sesi PPPoE yang tercatat aktif: 1', $this->sent[0]);

        $this->artisan('noc:monitor');
        $this->assertCount(1, $this->sent);   // tidak diulang

        $this->up = true;
        $this->via = 'tcp/8291';
        $this->artisan('noc:monitor');
        $this->assertCount(2, $this->sent);
        $this->assertStringContainsString('Router MT-Pusat (10.10.10.1) kembali online', $this->sent[1]);

        // Gagal sekali setelah pulih tidak langsung memicu peringatan (penghitung direset)
        $this->up = false;
        $this->artisan('noc:monitor');
        $this->assertCount(2, $this->sent);
    }

    public function test_router_page_shows_status_and_manual_check_sends_no_alert(): void
    {
        $root = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($root)->get(route('nas.index'))->assertOk()->assertSee('Belum dicek')->assertSee('Cek status sekarang');

        $this->up = false;
        $this->post(route('nas.check-status'))->assertSessionHas('success', 'Status router diperbarui: 0 online, 1 tidak merespons.');
        $this->post(route('nas.check-status'));
        $this->assertSame([], $this->sent);   // cek manual tidak mengirim peringatan / tidak menghitung gagal
        $this->get(route('nas.index'))->assertSee('Tidak merespons');

        $this->up = true;
        $this->post(route('nas.check-status'));
        $this->get(route('nas.index'))->assertSee('Online')->assertSee('ping · 1 sesi');

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->post(route('nas.check-status'))->assertForbidden();
    }

    public function test_real_probe_reports_unreachable_address(): void
    {
        // TEST-NET-1 (RFC 5737): tidak pernah dipakai di jaringan nyata
        $r = (new NasMonitor())->probe('192.0.2.1');
        $this->assertFalse($r['up']);
    }
}
