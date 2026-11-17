<?php

namespace Tests\Feature;

use App\Jobs\SendTicketToWhatsAppGroupJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\Customer;
use App\Models\NetworkIncident;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\Package;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Gangguan massal: pelanggan terdampak, tiket induk & tiket tertaut, WhatsApp, pemulihan sekaligus. */
class NetworkIncidentTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private OltPonPort $port1;
    private OltPonPort $port3;
    private Package $pkg;
    private User $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed(\Database\Seeders\WhatsappTemplateSeeder::class);
        $this->pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
        $this->olt = Olt::create(['name' => 'OLT A', 'ip_address' => '10.0.0.5', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true]);
        $this->port1 = OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        $this->port3 = OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 3, 'status' => 'active']);
        $this->teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
    }

    private function customer(string $name, array $extra = [], string $status = 'active'): Customer
    {
        static $n = 0;
        $n++;
        $c = Customer::create(array_merge(['customer_code' => sprintf('0020020%05d', $n), 'username' => "g{$n}@net", 'name' => $name,
            'package_id' => $this->pkg->id, 'phone' => '0812000' . $n], $extra));
        $c->forceFill(['status' => $status, 'is_active' => $status === 'active'])->saveQuietly();

        return $c->fresh();
    }

    public function test_affected_customers_per_scope(): void
    {
        $a = $this->customer('Port1 Posisi', ['olt_id' => $this->olt->id, 'onu_index' => '1.1.1.4']);
        $b = $this->customer('Port3', ['olt_id' => $this->olt->id, 'onu_index' => '1.1.3.2']);
        $c = $this->customer('Port1 Lewat Scan', ['username' => '20391582228@nextlink']);   // belum tertaut, cocok lewat nama ONU
        $this->customer('Port1 Terisolir', ['olt_id' => $this->olt->id, 'onu_index' => '1.1.1.9'], 'suspended');
        Cache::put("olt_port_data_{$this->port1->id}", [['index' => '1.1.1.7', 'name' => '20391582228', 'sn' => 'aa:bb:cc:00:11:22', 'status' => 'online']], 600);
        $odp = Odp::create(['id' => 'ODP-X', 'name' => 'ODP X']);
        $d = $this->customer('Di ODP', ['odp_id' => 'ODP-X']);

        $svc = app(\App\Services\Network\IncidentService::class);
        $port = $svc->affected('port', (string) $this->port1->id);
        $this->assertSame('OLT OLT A port 1/1', $port['label']);
        $this->assertEqualsCanonicalizing([$a->id, $c->id], $port['customers']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $svc->affected('olt', (string) $this->olt->id)['customers']->pluck('id')->all());
        $this->assertSame([$d->id], $svc->affected('odp', 'ODP-X')['customers']->pluck('id')->all());

        $this->actingAs($this->teknisi)->getJson(route('noc.incidents.preview', ['scope_type' => 'port', 'scope_id' => $this->port1->id]))
            ->assertOk()->assertJson(['count' => 2, 'with_phone' => 2]);
    }

    public function test_open_links_tickets_notifies_and_resolves_everything_at_once(): void
    {
        $a = $this->customer('Andi', ['olt_id' => $this->olt->id, 'onu_index' => '1.1.1.1']);
        $b = $this->customer('Budi', ['olt_id' => $this->olt->id, 'onu_index' => '1.1.1.2', 'phone' => null]);
        $old = Ticket::create(['customer_id' => $a->id, 'type' => 'gangguan', 'status' => 'open', 'priority' => 'medium', 'subject' => 'Internet mati', 'description' => 'x']);
        $other = Ticket::create(['customer_id' => $a->id, 'type' => 'aktivasi', 'status' => 'open', 'priority' => 'medium', 'subject' => 'x', 'description' => 'x']);
        $this->actingAs($this->teknisi);

        $this->post(route('noc.incidents.store'), ['scope_type' => 'port', 'scope_id' => $this->port1->id, 'title' => 'Feeder putus',
            'estimate' => 'sore ini', 'priority' => 'urgent', 'notify' => 1])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $incident = NetworkIncident::firstOrFail();
        $this->assertSame(2, $incident->customers()->count());
        $this->assertSame('[Gangguan massal] Feeder putus', $incident->ticket->subject);
        $this->assertSame($incident->id, $old->fresh()->incident_id);          // tiket gangguan lama ikut tertaut
        $this->assertNull($other->fresh()->incident_id);                       // tiket aktivasi tidak
        $this->assertTrue($old->replies()->where('message', 'like', '%gangguan massal%')->exists());
        Queue::assertPushed(SendWhatsappMessageJob::class, 1);                 // hanya yang punya No. HP
        Queue::assertPushed(SendWhatsappMessageJob::class, fn ($j) => $j->customDate === 'sore ini');
        $this->assertSame(1, $incident->fresh()->notified_count);

        // Tidak boleh dua gangguan terbuka untuk cakupan yang sama
        $this->post(route('noc.incidents.store'), ['scope_type' => 'port', 'scope_id' => $this->port1->id, 'priority' => 'high'])
            ->assertSessionHas('error');

        // Tiket gangguan baru dari pelanggan terdampak otomatis tertaut
        $new = Ticket::create(['customer_id' => $b->id, 'type' => 'gangguan', 'status' => 'open', 'priority' => 'medium', 'subject' => 'Lambat', 'description' => 'x']);
        $this->assertSame($incident->id, $new->fresh()->incident_id);

        $this->get(route('noc.incidents.show', $incident))->assertOk()->assertSee('Feeder putus')->assertSee('Andi')->assertSee('Tandai pulih');
        $this->get(route('tickets.show', $new))->assertOk()->assertSee('Bagian dari gangguan massal');

        Queue::fake();   // hitung ulang job grup WA untuk pemulihan
        $this->post(route('noc.incidents.resolve', $incident), ['resolution_notes' => 'Disambung ulang', 'notify' => 1])->assertSessionHas('success');
        $incident->refresh();
        $this->assertSame('resolved', $incident->status);
        $this->assertSame('resolved', $incident->ticket->status);
        $this->assertSame('resolved', $old->fresh()->status);
        $this->assertSame('resolved', $new->fresh()->status);
        $this->assertSame('open', $other->fresh()->status);
        Queue::assertPushed(SendTicketToWhatsAppGroupJob::class, 1);            // satu pesan grup (tiket induk), bukan per tiket
        Queue::assertPushed(SendWhatsappMessageJob::class, fn ($j) => $j->templateId === WhatsappTemplate::where('type', 'gangguan_pulih')->value('id'));
        $this->assertSame(1, $incident->resolved_notified_count);

        // Setelah pulih, tiket baru tidak lagi tertaut
        $later = Ticket::create(['customer_id' => $a->id, 'type' => 'gangguan', 'status' => 'open', 'priority' => 'medium', 'subject' => 'x', 'description' => 'x']);
        $this->assertNull($later->fresh()->incident_id);
    }

    public function test_whatsapp_job_accepts_free_text_estimate(): void
    {
        $c = $this->customer('Citra');
        $tpl = WhatsappTemplate::where('type', 'gangguan_masal')->firstOrFail();
        $wa = new class extends WhatsAppService {
            public array $sent = [];
            public function __construct() {}
            public function sendMessage($target, $message, $logId = null): bool { $this->sent[] = $message; return true; }
        };
        (new SendWhatsappMessageJob($c->id, $tpl->id, 'sore ini'))->handle($wa);
        $this->assertStringContainsString('Estimasi perbaikan: *sore ini*', $wa->sent[0]);
        $this->assertStringContainsString('Halo *Citra*', $wa->sent[0]);
    }

    public function test_access_and_pages(): void
    {
        $this->actingAs($this->teknisi)->get(route('noc.incidents.index'))->assertOk()->assertSee('Gangguan Massal');
        $this->get(route('noc.incidents.create', ['scope_type' => 'port', 'scope_id' => $this->port1->id]))->assertOk()->assertSee('Laporkan Gangguan Massal');
        $this->get(route('olts.show-port', [$this->olt, $this->port1]))->assertOk()->assertSee('Laporkan gangguan massal');

        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);
        $this->actingAs($kasir)->get(route('noc.incidents.index'))->assertForbidden();
        $this->post(route('noc.incidents.store'), ['scope_type' => 'olt', 'scope_id' => $this->olt->id, 'priority' => 'high'])->assertForbidden();
    }
}
