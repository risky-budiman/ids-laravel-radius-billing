<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Services\CustomerLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Simulasi siklus tagihan harian (app:process-billing) selama beberapa bulan.
 * QUEUE_CONNECTION=sync di phpunit.xml: job generate invoice & isolir langsung dijalankan.
 */
class BillingCycleSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function activeCustomer(string $type, string $method, string $activatedOn): Customer
    {
        $this->travelTo(CarbonImmutable::parse($activatedOn . ' 10:00'));
        $package = Package::create(['name' => 'Paket 10M', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M']);
        $c = Customer::create([
            'customer_code' => "C-$type-$method", 'username' => "u_{$type}_{$method}", 'name' => "Pelanggan $type $method",
            'package_id' => $package->id, 'billing_type' => $type, 'billing_method' => $method, 'billing_due_day' => 20,
            'is_active' => false, 'status' => Customer::STATUS_NEW,
        ]);
        $this->assertTrue(app(CustomerLifecycleService::class)->activate($c->fresh()));

        return $c->fresh();
    }

    /** Jalankan billing harian dari besok sampai $until; kembalikan tanggal isolir pertama (atau null). */
    private function runDailyBilling(Customer $c, string $from, string $until, bool $stopOnSuspend = true): ?string
    {
        for ($d = CarbonImmutable::parse($from); $d->lte(CarbonImmutable::parse($until)); $d = $d->addDay()) {
            $this->travelTo($d->setTime(0, 1));
            $this->artisan('app:process-billing')->assertSuccessful();
            if ($stopOnSuspend && $c->fresh()->status === Customer::STATUS_SUSPENDED) {
                return $d->toDateString();
            }
        }

        return null;
    }

    public function test_postpaid_cycle_customer_who_never_pays_gets_suspended_after_first_due_date(): void
    {
        $c = $this->activeCustomer('postpaid', 'cycle', '2026-03-10');

        $suspendedOn = $this->runDailyBilling($c, '2026-03-11', '2026-07-31');

        $first = Invoice::where('customer_id', $c->id)->orderBy('id')->first();
        $this->assertNotNull($first, 'invoice pertama tidak terbit');
        $this->assertSame('2026-04-20', $first->due_date->toDateString());
        $this->assertSame('2026-04-21', $suspendedOn, 'isolir harus sehari setelah jatuh tempo invoice yang belum dibayar');
    }

    public function test_prepaid_fixed_customer_who_never_pays_gets_suspended(): void
    {
        $c = $this->activeCustomer('prepaid', 'fixed', '2026-03-10');

        $suspendedOn = $this->runDailyBilling($c, '2026-03-11', '2026-07-31');

        $first = Invoice::where('customer_id', $c->id)->orderBy('id')->first();
        $this->assertNotNull($first);
        $this->assertNotNull($suspendedOn, 'pelanggan prabayar fixed yang tidak membayar tidak pernah diisolir');
        $this->assertSame($first->due_date->addDay()->toDateString(), $suspendedOn);
    }

    public function test_postpaid_customer_paying_on_time_is_never_suspended_and_late_payer_is_restored(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        \App\Models\BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        $c = $this->activeCustomer('postpaid', 'cycle', '2026-03-10');
        $pay = fn () => Invoice::where('customer_id', $c->id)->where('status', 'unpaid')->get()
            ->each(fn ($i) => app(\App\Services\InvoiceService::class)->markAsPaid($i));

        // Bayar setiap tanggal 15 -> tidak pernah terisolir
        foreach (['2026-04-14', '2026-05-14', '2026-06-14'] as $until) {
            $this->assertNull($this->runDailyBilling($c, CarbonImmutable::parse($until)->startOfMonth()->toDateString(), $until));
            $this->travelTo(CarbonImmutable::parse($until)->addDay()->setTime(9, 0));
            $pay();
        }
        $this->assertNull($this->runDailyBilling($c, '2026-06-15', '2026-06-30'));

        // Juli tidak dibayar -> isolir 21 Juli; dua invoice menunggak, bayar satu -> tetap isolir
        $this->assertSame('2026-07-21', $this->runDailyBilling($c, '2026-07-01', '2026-08-31'));
        $this->runDailyBilling($c, '2026-07-22', '2026-08-21', false);
        $unpaid = Invoice::where('customer_id', $c->id)->where('status', 'unpaid')->orderBy('id')->get();
        $this->assertCount(2, $unpaid);
        app(\App\Services\InvoiceService::class)->markAsPaid($unpaid[1]);
        $this->assertSame(Customer::STATUS_SUSPENDED, $c->fresh()->status, 'masih ada tunggakan Juli');

        app(\App\Services\InvoiceService::class)->markAsPaid($unpaid[0]->fresh());
        $this->assertSame(Customer::STATUS_ACTIVE, $c->fresh()->status);
        $this->assertTrue((bool) $c->fresh()->is_active);
    }

    public function test_unpaid_booster_does_not_suspend_and_paying_booster_does_not_unsuspend(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        \App\Models\BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        $c = $this->activeCustomer('postpaid', 'cycle', '2026-03-10');
        $booster = Invoice::create(['customer_id' => $c->id, 'invoice_number' => 'INV-BOOST-99', 'amount' => 20000, 'subtotal' => 20000,
            'status' => 'unpaid', 'due_date' => '2026-03-12']);

        $this->assertNull($this->runDailyBilling($c, '2026-03-13', '2026-03-31'), 'booster belum dibayar bukan alasan isolir');

        $this->assertSame('2026-04-21', $this->runDailyBilling($c, '2026-04-01', '2026-04-30'));
        app(\App\Services\InvoiceService::class)->markAsPaid($booster->fresh());
        $this->assertSame(Customer::STATUS_SUSPENDED, $c->fresh()->status);
    }

    public function test_prepaid_renewal_payment_extends_from_previous_expiry(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        \App\Models\BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        $c = $this->activeCustomer('prepaid', 'renewal', '2026-03-10');
        $expiry = $c->expired_at->toDateString();

        $this->assertNull($this->runDailyBilling($c, '2026-03-11', CarbonImmutable::parse($expiry)->subDay()->toDateString()));
        $inv = Invoice::where('customer_id', $c->id)->where('status', 'unpaid')->sole();
        $this->assertSame($expiry, $inv->due_date->toDateString());
        $this->assertSame($expiry, $c->fresh()->expired_at->toDateString(), 'invoice terbit tidak boleh memperpanjang masa aktif');

        app(\App\Services\InvoiceService::class)->markAsPaid($inv);
        $this->assertSame(CarbonImmutable::parse($expiry)->addDays(30)->toDateString(), $c->fresh()->expired_at->toDateString());
    }

    /**
     * Keputusan bisnis: pelanggan terisolir TETAP ditagih tiap bulan; batal (terminate),
     * menunggu dismantle & dicabut tidak dibuatkan invoice lagi.
     */
    public function test_suspended_customers_keep_being_invoiced_but_terminated_ones_do_not(): void
    {
        $suspended = $this->activeCustomer('postpaid', 'cycle', '2026-03-10');
        $this->assertSame('2026-04-21', $this->runDailyBilling($suspended, '2026-03-11', '2026-04-30'));
        $this->runDailyBilling($suspended, '2026-05-01', '2026-06-01', false);
        // Mar (prorata), Apr, Mei: tetap terbit walau terisolir sejak 21 Apr
        $this->assertSame(3, Invoice::where('customer_id', $suspended->id)->count());

        foreach ([Customer::STATUS_CANCELED, Customer::STATUS_WAITING_DISMANTLE, Customer::STATUS_DISMANTLED] as $status) {
            $c = Customer::create([
                'customer_code' => "C-$status", 'username' => "u_$status", 'name' => "Pelanggan $status",
                'package_id' => $suspended->package_id, 'billing_type' => 'postpaid', 'billing_method' => 'cycle',
                'is_active' => false, 'status' => Customer::STATUS_NEW,
            ]);
            // Jadwal tagihan sudah jatuh tempo, tapi statusnya tidak ditagih
            Customer::whereKey($c->id)->update(['status' => $status, 'billing_next_date' => '2026-06-01', 'billing_due_date' => '2026-06-20']);
        }

        $this->travelTo(CarbonImmutable::parse('2026-06-02 00:01'));
        $this->artisan('app:process-billing')->assertSuccessful();

        foreach ([Customer::STATUS_CANCELED, Customer::STATUS_WAITING_DISMANTLE, Customer::STATUS_DISMANTLED] as $status) {
            $this->assertSame(0, Invoice::whereHas('customer', fn ($q) => $q->where('status', $status))->count(), "status $status tidak boleh ditagih");
        }
        $this->assertSame([Customer::STATUS_ACTIVE, Customer::STATUS_SUSPENDED], Customer::BILLABLE_STATUSES);
    }

    public function test_prepaid_renewal_customer_who_never_pays_gets_suspended_when_period_ends(): void
    {
        $c = $this->activeCustomer('prepaid', 'renewal', '2026-03-10');
        $paidUntil = $c->expired_at->toDateString();          // masa aktif awal (30 hari)

        $suspendedOn = $this->runDailyBilling($c, '2026-03-11', '2026-07-31');

        $this->assertNotNull($suspendedOn, 'masa aktif bertambah sendiri tanpa pembayaran — tidak pernah diisolir');
        $this->assertSame(CarbonImmutable::parse($paidUntil)->addDay()->toDateString(), $suspendedOn);
    }
}
