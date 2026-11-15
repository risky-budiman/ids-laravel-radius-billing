<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AutoDismantleService;
use App\Services\CustomerLifecycleService;
use App\Services\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pengajuan dismantle otomatis untuk pelanggan yang terisolir lebih dari N hari.
 */
class AutoDismantleTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function suspendedSince(string $date, bool $withOverdue = true): Customer
    {
        $n = ++$this->seq;
        $this->travelTo(CarbonImmutable::parse($date));
        $c = Customer::create(['customer_code' => "C-AD-$n", 'username' => "ad$n", 'name' => "Pelanggan $n",
            'is_active' => true, 'status' => Customer::STATUS_ACTIVE]);
        Customer::whereKey($c->id)->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true, 'activated_at' => '2026-01-01']);
        if ($withOverdue) {
            Invoice::create(['customer_id' => $c->id, 'invoice_number' => "INV-AD-$n", 'amount' => 150000, 'subtotal' => 150000,
                'status' => 'unpaid', 'due_date' => CarbonImmutable::parse($date)->subDay()]);
        }
        app(CustomerLifecycleService::class)->suspend($c->fresh());
        $this->travelBack();

        return $c->fresh();
    }

    private function enable(int $days = 60): void
    {
        app(AutoDismantleService::class)->saveSettings(true, $days);
    }

    public function test_suspended_at_follows_status(): void
    {
        $c = $this->suspendedSince('2026-09-01 10:00');
        $this->assertSame('2026-09-01', $c->suspended_at->toDateString());

        $c->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        $this->assertNull($c->fresh()->suspended_at);
    }

    public function test_long_suspended_customers_are_submitted_once_with_a_ticket_note(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 01:30'));
        $old = $this->suspendedSince('2026-09-01 10:00');      // 75 hari
        $recent = $this->suspendedSince('2026-10-20 10:00');   // 26 hari
        $noArrears = $this->suspendedSince('2026-08-01 10:00', withOverdue: false);   // isolir manual, tanpa tunggakan
        $this->travelTo(CarbonImmutable::parse('2026-11-15 01:30'));

        $this->artisan('app:auto-dismantle')->assertSuccessful();
        $this->assertSame(Customer::STATUS_SUSPENDED, $old->fresh()->status, 'nonaktif secara default: tidak ada yang diajukan');

        $this->enable(60);
        $this->artisan('app:auto-dismantle')->assertSuccessful();

        $this->assertSame(Customer::STATUS_WAITING_DISMANTLE, $old->fresh()->status);
        $this->assertSame(Customer::STATUS_SUSPENDED, $recent->fresh()->status);
        $this->assertSame(Customer::STATUS_SUSPENDED, $noArrears->fresh()->status, 'isolir manual tanpa tunggakan tidak boleh didismantle');
        $ticket = Ticket::where('customer_id', $old->id)->where('type', 'dismantle')->sole();
        $this->assertStringContainsString('Diajukan otomatis', $ticket->replies()->where('is_system', true)->latest('id')->value('message'));
        $this->assertStringContainsString('74 hari', $ticket->replies()->where('is_system', true)->latest('id')->value('message'));
        $this->assertNotContains(Customer::STATUS_WAITING_DISMANTLE, Customer::BILLABLE_STATUSES, 'tagihan bulanan berhenti');

        // Run berikutnya tidak membuat tiket kedua
        $this->artisan('app:auto-dismantle')->assertSuccessful();
        $this->assertSame(1, Ticket::where('customer_id', $old->id)->where('type', 'dismantle')->count());
    }

    public function test_paying_then_canceling_the_dismantle_reactivates_the_customer(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 01:30'));
        $c = $this->suspendedSince('2026-09-01 10:00');
        $this->travelTo(CarbonImmutable::parse('2026-11-15 01:30'));
        $this->enable(60);
        app(AutoDismantleService::class)->run();
        $this->assertSame(Customer::STATUS_WAITING_DISMANTLE, $c->fresh()->status);

        // Melunasi saat menunggu dismantle: belum dibuka (dismantle masih berjalan)
        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        app(InvoiceService::class)->markAsPaid(Invoice::where('customer_id', $c->id)->sole());
        $this->assertSame(Customer::STATUS_WAITING_DISMANTLE, $c->fresh()->status);

        // Tiket dismantle dibatalkan -> tanpa tunggakan, langsung aktif
        $ticket = Ticket::where('customer_id', $c->id)->where('type', 'dismantle')->sole();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
        $this->putJson("/api/v1/admin/tickets/{$ticket->id}", ['status' => 'canceled'])->assertOk();

        $this->assertSame(Customer::STATUS_ACTIVE, $c->fresh()->status);
        $this->assertTrue((bool) $c->fresh()->is_active);
        $this->assertNull($c->fresh()->suspended_at);
    }

    public function test_canceling_while_still_in_arrears_returns_to_suspended(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 01:30'));
        $c = $this->suspendedSince('2026-09-01 10:00');
        $this->travelTo(CarbonImmutable::parse('2026-11-15 01:30'));
        $this->enable(60);
        app(AutoDismantleService::class)->run();

        $ticket = Ticket::where('customer_id', $c->id)->where('type', 'dismantle')->sole();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
        $this->putJson("/api/v1/admin/tickets/{$ticket->id}", ['status' => 'canceled'])->assertOk();

        $this->assertSame(Customer::STATUS_SUSPENDED, $c->fresh()->status);
        $this->assertFalse((bool) $c->fresh()->is_active);
    }

    public function test_settings_page_saves_and_previews_candidates(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));
        $c = $this->suspendedSince('2026-09-01 10:00');
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)->post(route('settings.isolation.dismantle'), ['auto_dismantle_enabled' => '1', 'auto_dismantle_days' => 7])
            ->assertSessionHasErrors('auto_dismantle_days');
        $this->actingAs($admin)->post(route('settings.isolation.dismantle'), ['auto_dismantle_enabled' => '1', 'auto_dismantle_days' => 45])
            ->assertRedirect(route('settings.isolation'));

        $this->assertTrue(app(AutoDismantleService::class)->enabled());
        $this->assertSame(45, app(AutoDismantleService::class)->days());
        $this->actingAs($admin)->get(route('settings.isolation'))->assertOk()
            ->assertSee('Pengajuan dismantle otomatis')->assertSee($c->name)->assertSee('75 hari');
    }

    public function test_scheduled_daily(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn ($e) => str_contains($e->command ?? '', 'app:auto-dismantle') && $e->expression === '30 1 * * *'));
    }
}
