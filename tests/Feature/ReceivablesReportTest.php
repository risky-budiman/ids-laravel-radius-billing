<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Reports\ReceivablesService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laporan piutang (umur tagihan) + daftar pelanggan yang akan diisolir pada run berikutnya.
 */
class ReceivablesReportTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));
    }

    private function customer(string $status = Customer::STATUS_ACTIVE, array $attrs = []): Customer
    {
        $n = ++$this->seq;
        $c = Customer::create(array_merge(['customer_code' => "C-AR-$n", 'username' => "ar$n", 'name' => "Pelanggan $n",
            'phone' => '08120000' . $n, 'is_active' => true, 'status' => Customer::STATUS_ACTIVE], $attrs));
        Customer::whereKey($c->id)->update(array_merge(['status' => $status, 'is_active' => $status === Customer::STATUS_ACTIVE], $attrs));

        return $c->fresh();
    }

    private function invoice(Customer $c, string $due, float $amount, string $number = null, string $status = 'unpaid'): Invoice
    {
        return Invoice::create(['customer_id' => $c->id, 'invoice_number' => $number ?? 'INV-AR-' . (++$this->seq), 'amount' => $amount,
            'subtotal' => $amount, 'status' => $status, 'due_date' => $due]);
    }

    public function test_aging_buckets_and_per_customer_totals(): void
    {
        $a = $this->customer();
        $this->invoice($a, '2026-11-20', 150000);                  // belum jatuh tempo
        $this->invoice($a, '2026-11-15', 150000);                  // jatuh tempo hari ini = belum terlambat
        $b = $this->customer(Customer::STATUS_SUSPENDED);
        $this->invoice($b, '2026-10-20', 200000);                  // 26 hari
        $this->invoice($b, '2026-09-20', 200000);                  // 56 hari
        $d = $this->customer(Customer::STATUS_DISMANTLED, ['is_active' => false]);
        $this->invoice($d, '2026-07-01', 300000);                  // 137 hari
        $this->invoice($a, '2026-11-01', 20000, 'INV-BOOST-9');     // booster: bukan piutang
        $this->invoice($a, '2026-10-01', 99000, null, 'paid');     // lunas

        $r = app(ReceivablesService::class)->build();

        $this->assertEquals(300000, $r['summary']['current']['amount']);
        $this->assertEquals(200000, $r['summary']['d30']['amount']);
        $this->assertEquals(200000, $r['summary']['d60']['amount']);
        $this->assertEquals(0, $r['summary']['d90']['amount']);
        $this->assertEquals(300000, $r['summary']['d90plus']['amount']);
        $this->assertEquals(1000000, $r['total']);
        $this->assertSame(5, $r['totalCount']);
        $this->assertEquals(700000, $r['overdueAmount']);
        $this->assertEquals(500000, $r['over30Amount']);

        // Paling lama menunggak di atas
        $this->assertSame([$d->id, $b->id, $a->id], $r['customers']->pluck('customer.id')->all());
        $this->assertSame(137, $r['customers'][0]['max_days']);
    }

    public function test_lists_active_customers_suspended_by_the_next_billing_run(): void
    {
        $dueToday = $this->customer();
        $this->invoice($dueToday, '2026-11-15', 150000);
        $dueTomorrow = $this->customer();
        $this->invoice($dueTomorrow, '2026-11-16', 150000);
        $alreadySuspended = $this->customer(Customer::STATUS_SUSPENDED);
        $this->invoice($alreadySuspended, '2026-11-01', 150000);
        $onlyBooster = $this->customer();
        $this->invoice($onlyBooster, '2026-11-10', 20000, 'INV-BOOST-1');
        $renewal = $this->customer(Customer::STATUS_ACTIVE, ['billing_type' => 'prepaid', 'billing_method' => 'renewal', 'expired_at' => '2026-11-15']);

        $list = app(ReceivablesService::class)->toBeSuspended(CarbonImmutable::today());

        $this->assertEqualsCanonicalizing([$dueToday->id, $renewal->id], $list->pluck('customer.id')->all());

        // Harus cocok dengan yang benar-benar diisolir oleh billing besok
        $this->travelTo(CarbonImmutable::parse('2026-11-16 00:01'));
        \Illuminate\Support\Facades\Process::fake();
        $this->artisan('app:process-billing')->assertSuccessful();
        $suspendedNow = Customer::whereIn('id', [$dueToday->id, $dueTomorrow->id, $onlyBooster->id, $renewal->id])
            ->where('status', Customer::STATUS_SUSPENDED)->pluck('id')->all();
        $this->assertEqualsCanonicalizing($list->pluck('customer.id')->all(), $suspendedNow);
    }

    public function test_page_and_export_for_cashier_not_for_technician(): void
    {
        $c = $this->customer();
        $this->invoice($c, '2026-11-01', 150000);

        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);
        $this->actingAs($kasir)->get(route('receivables.index'))->assertOk()
            ->assertSee('Piutang Pelanggan')->assertSee($c->name)->assertSee('1–30 hari');
        $xlsx = $this->actingAs($kasir)->get(route('receivables.export'));
        $xlsx->assertOk()->assertDownload('Piutang-2026-11-15.xlsx');
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());

        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]))
            ->get(route('receivables.index'))->assertForbidden();
    }
}
