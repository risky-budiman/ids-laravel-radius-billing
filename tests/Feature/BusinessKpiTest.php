<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Services\Reports\BusinessKpiService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * KPI bisnis: MRR, ARPU, churn, pelanggan baru vs berhenti — angka dihitung manual di komentar.
 */
class BusinessKpiTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));
    }

    private function customer(float $price, string $status, ?string $activated, ?string $terminated = null, array $extra = []): Customer
    {
        $n = ++$this->seq;
        $pkg = Package::create(['name' => "Paket $n", 'type' => 'pppoe', 'price' => $price, 'upload_speed' => '10M', 'download_speed' => '10M']);
        $c = Customer::create(array_merge(['customer_code' => "C-KPI-$n", 'username' => "kpi$n", 'name' => "Pelanggan $n",
            'package_id' => $pkg->id, 'is_active' => true, 'status' => Customer::STATUS_ACTIVE], $extra));
        Customer::whereKey($c->id)->update(['status' => $status, 'activated_at' => $activated, 'terminated_at' => $terminated,
            'created_at' => $activated ?? '2026-11-01']);

        return $c->fresh();
    }

    public function test_monthly_mrr_arpu_churn_and_flows(): void
    {
        $a = $this->customer(150000, Customer::STATUS_ACTIVE, '2026-08-10');
        $b = $this->customer(200000, Customer::STATUS_DISMANTLED, '2026-09-05', '2026-10-20', ['discount_type' => 'percentage', 'discount_value' => 10]); // 180.000
        $this->customer(100000, Customer::STATUS_SUSPENDED, '2026-10-01');                 // terisolir tetap dihitung
        $this->customer(999000, Customer::STATUS_WAITING_ACTIVATION, null);                // belum pernah aktif
        $this->customer(888000, Customer::STATUS_CANCELED, null, '2026-10-05');            // batal sebelum aktif: bukan churn

        $d = app(BusinessKpiService::class)->build(6);
        $sep = $d['rows']['2026-09'];
        $oct = $d['rows']['2026-10'];
        $nov = $d['rows']['2026-11'];

        // September: awal A; baru B; akhir A+B = 150.000 + 180.000
        $this->assertSame([1, 1, 0, 2], [$sep['start_count'], $sep['new'], $sep['stopped'], $sep['end_count']]);
        $this->assertEquals(330000, $sep['mrr']);
        $this->assertEquals(165000, $sep['arpu']);

        // Oktober: awal A+B; baru C; B berhenti 20 Okt -> churn 1/2 = 50%; akhir A+C
        $this->assertSame([2, 1, 1, 2], [$oct['start_count'], $oct['new'], $oct['stopped'], $oct['end_count']]);
        $this->assertEquals(50.0, $oct['churn_pct']);
        $this->assertEquals(250000, $oct['mrr']);
        $this->assertEquals(125000, $oct['arpu']);

        $this->assertTrue($nov['is_current']);
        $this->assertEquals(250000, $d['current']['mrr']);
        $this->assertEquals(0.0, $d['mrrChange']);
        $this->assertSame(3, $d['totalNew']);       // A (Agu), B (Sep), C (Okt)
        $this->assertSame(1, $d['totalStopped']);   // pembatalan sebelum aktif tidak dihitung
    }

    public function test_billed_and_collected_exclude_tax_booster_and_installation(): void
    {
        $c = $this->customer(150000, Customer::STATUS_ACTIVE, '2026-08-10');
        $this->travelTo(CarbonImmutable::parse('2026-10-01 00:01'));
        $inv = Invoice::create(['customer_id' => $c->id, 'invoice_number' => 'INV-KPI-1', 'subtotal' => 150000, 'discount_amount' => 15000,
            'tax_amount' => 14850, 'amount' => 149850, 'status' => 'unpaid', 'due_date' => '2026-10-20']);
        Invoice::create(['customer_id' => $c->id, 'invoice_number' => 'INV-BOOST-5', 'subtotal' => 20000, 'amount' => 20000, 'status' => 'paid',
            'paid_at' => now(), 'due_date' => '2026-10-02']);
        $this->travelTo(CarbonImmutable::parse('2026-11-03 09:00'));
        $inv->update(['status' => 'paid', 'paid_at' => now()]);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));

        $d = app(BusinessKpiService::class)->build(6);

        $this->assertEquals(135000, $d['rows']['2026-10']['billed']);     // 150.000 - diskon 15.000, tanpa PPN
        $this->assertEquals(0, $d['rows']['2026-10']['collected']);
        $this->assertEquals(135000, $d['rows']['2026-11']['collected']);  // dibayar 3 Nov
    }

    public function test_terminated_at_is_recorded_and_cleared_by_status_changes(): void
    {
        $c = $this->customer(150000, Customer::STATUS_ACTIVE, '2026-08-10');

        $c->update(['status' => Customer::STATUS_DISMANTLED, 'is_active' => false]);
        $this->assertSame('2026-11-15', $c->fresh()->terminated_at->toDateString());

        $c->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        $this->assertNull($c->fresh()->terminated_at);
    }

    public function test_page_and_export_for_admin_only(): void
    {
        $this->customer(150000, Customer::STATUS_ACTIVE, '2026-08-10');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('kpi.index', ['months' => 6]))->assertOk()
            ->assertSee('KPI Bisnis')->assertSee('MRR per bulan')->assertSee('Rp 150.000');
        $xlsx = $this->actingAs($admin)->get(route('kpi.export'));
        $xlsx->assertOk()->assertDownload('KPI-Bisnis-2026-11-15.xlsx');
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());

        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]))->get(route('kpi.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('kpi.index', ['months' => 7]))->assertSessionHasErrors('months');
    }
}
