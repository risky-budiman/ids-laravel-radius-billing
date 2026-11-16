<?php

namespace Tests\Feature;

use App\Jobs\DeprovisionOnuJob;
use App\Jobs\ProvisionOnuJob;
use App\Models\Customer;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\OltWorkItem;
use App\Models\Package;
use App\Models\User;
use App\Services\Network\OltWorkVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Pekerjaan manual di OLT: tercatat dari job ONU, ditutup otomatis lewat cek SNMP atau ditandai teknisi.
 */
class OltWorkItemTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;
    private OltPonPort $p1;
    private OltPonPort $p2;
    private Customer $c;

    protected function setUp(): void
    {
        parent::setUp();
        $this->olt = Olt::create(['name' => 'OLT Kerja', 'ip_address' => '10.0.0.70', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'HSGQ_EPON', 'is_active' => true]);
        $this->p1 = OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 1, 'status' => 'active']);
        $this->p2 = OltPonPort::create(['olt_id' => $this->olt->id, 'slot' => 1, 'pon_port' => 2, 'status' => 'active']);
        $pkg = Package::create(['name' => 'P20', 'type' => 'pppoe', 'price' => 200000, 'upload_speed' => '20M', 'download_speed' => '20M',
            'is_active' => true, 'olt_tcont_profile' => 'UP-20M']);
        $this->c = Customer::create(['customer_code' => '001001009901', 'username' => 'kerja@net', 'name' => 'Pelanggan Kerja',
            'package_id' => $pkg->id, 'phone' => '0811', 'onu_sn' => '00:1A:69:43:12:29', 'onu_index' => '1/1/1', 'vlan_id' => 200]);
        $this->c->update(['olt_id' => $this->olt->id]);
    }

    public function test_jobs_record_work_without_duplicates(): void
    {
        (new ProvisionOnuJob($this->c->id))->handle();
        (new ProvisionOnuJob($this->c->id))->handle();

        $item = OltWorkItem::sole();
        $this->assertSame(['provision', 'pending', '00:1A:69:43:12:29', 200, 'UP-20M', $this->c->id],
            [$item->type, $item->status, $item->onu_sn, $item->vlan, $item->profile, $item->customer_id]);

        // Hapus untuk SN yang sama membatalkan "daftarkan" yang masih menunggu
        (new DeprovisionOnuJob($this->olt->id, '1/1/1:3', '00:1A:69:43:12:29'))->handle();
        $this->assertSame('dismissed', $item->fresh()->status);
        $this->assertSame(1, OltWorkItem::pending()->where('type', 'delete')->count());
    }

    public function test_verifier_closes_items_seen_on_the_olt(): void
    {
        $prov = OltWorkItem::record($this->olt->id, 'provision', ['onu_sn' => '00:1A:69:43:12:29']);
        $del = OltWorkItem::record($this->olt->id, 'delete', ['onu_sn' => 'E8:68:19:02:FF:70']);
        Cache::put("olt_port_data_{$this->p1->id}", [['index' => '1.1.1.1', 'sn' => '00:1a:69:43:12:29']], 600);   // format MAC berbeda tetap cocok

        // Port 2 belum terbaca: "hapus" belum boleh dianggap selesai
        $r = app(OltWorkVerifier::class)->verify($this->olt, cacheOnly: true);
        $this->assertSame(1, $r['done']);
        $this->assertSame(['done', 'auto'], [$prov->fresh()->status, $prov->fresh()->resolution]);
        $this->assertSame('pending', $del->fresh()->status);

        Cache::put("olt_port_data_{$this->p2->id}", [], 600);
        app(OltWorkVerifier::class)->verify($this->olt, cacheOnly: true);
        $this->assertSame('done', $del->fresh()->status);
    }

    public function test_technician_resolves_items_and_sees_them(): void
    {
        $item = OltWorkItem::record($this->olt->id, 'profile', ['onu_sn' => 'ZTEGC0000001', 'profile' => 'UP-50M', 'customer_id' => $this->c->id]);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi);

        $this->get(route('olts.show', $this->olt))->assertOk()->assertSee('Pekerjaan di OLT')->assertSee('Ganti profil T-CONT')->assertSee('UP-50M');
        $this->get(route('olts.index'))->assertOk()->assertSee('1 pekerjaan di OLT');

        $this->post(route('olts.work.resolve', [$this->olt, $item]), ['action' => 'done'])->assertRedirect();
        $item->refresh();
        $this->assertSame(['done', 'manual', $teknisi->id], [$item->status, $item->resolution, $item->resolved_by]);
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'olt_work_done')->exists());

        // Item OLT lain lewat URL OLT ini → 404
        $other = Olt::create(['name' => 'OLT Lain', 'ip_address' => '10.0.0.71', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'olt_type' => 'ZTE_C320', 'is_active' => true]);
        $foreign = OltWorkItem::record($other->id, 'delete', ['onu_sn' => 'X1']);
        $this->post(route('olts.work.resolve', [$this->olt, $foreign]), ['action' => 'dismiss'])->assertNotFound();
    }
}
