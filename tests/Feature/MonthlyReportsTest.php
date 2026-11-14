<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Reports\BankStatementService;
use App\Services\Reports\TicketRecapService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Rekap tiket (audit tim) dan rekening koran Kas & Bank, bulanan & tahunan.
 */
class MonthlyReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function staff(string $role, string $name = null): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true] + ($name ? ['name' => $name] : []));
    }

    // ---------------------------------------------------------------- Tiket

    private function ticketAt(string $created, string $type, ?User $staff, ?string $resolvedAt = null, string $finalStatus = 'resolved'): Ticket
    {
        $this->travelTo(CarbonImmutable::parse($created));
        $t = Ticket::create(['type' => $type, 'status' => 'open', 'priority' => 'medium', 'subject' => 'x', 'description' => 'y', 'assigned_to' => $staff?->id]);
        if ($resolvedAt) {
            $this->travelTo(CarbonImmutable::parse($resolvedAt));
            $t->update(['status' => $finalStatus]);
        } elseif ($finalStatus !== 'resolved') {
            $t->update(['status' => $finalStatus]);
        }
        $this->travelBack();

        return $t->fresh();
    }

    public function test_resolved_at_is_recorded_once_and_cleared_when_reopened(): void
    {
        $t = $this->ticketAt('2026-03-01 08:00', 'gangguan', null, '2026-03-01 10:00');
        $this->assertSame('2026-03-01 10:00', $t->resolved_at->format('Y-m-d H:i'));

        // RESOLVED -> CLOSED (administrasi) tidak menggeser waktu selesai
        $this->travelTo(CarbonImmutable::parse('2026-03-05 09:00'));
        $t->update(['status' => 'closed']);
        $this->assertSame('2026-03-01 10:00', $t->fresh()->resolved_at->format('Y-m-d H:i'));

        $t->update(['status' => 'in_progress']);
        $this->assertNull($t->fresh()->resolved_at);
    }

    public function test_monthly_ticket_recap_counts_work_per_technician_with_sla(): void
    {
        $budi = $this->staff('teknisi', 'Budi');
        $andi = $this->staff('teknisi', 'Andi');
        $this->staff('teknisi', 'Idle Tech');   // tanpa tiket tetap muncul

        $this->ticketAt('2026-03-02 08:00', 'gangguan', $budi, '2026-03-02 12:00');        // 4 jam, SLA ok
        $this->ticketAt('2026-03-03 08:00', 'gangguan', $budi, '2026-03-05 08:00');        // 48 jam, lewat SLA
        $this->ticketAt('2026-03-04 08:00', 'aktivasi', $andi, '2026-03-04 20:00');        // 12 jam
        $this->ticketAt('2026-03-10 08:00', 'gangguan', $andi);                             // masih terbuka
        $this->ticketAt('2026-03-11 08:00', 'dismantle', null, null, 'canceled');           // batal, tanpa petugas
        $this->ticketAt('2026-02-27 08:00', 'gangguan', $budi, '2026-03-01 08:00');        // masuk Feb, selesai Mar
        $this->ticketAt('2026-04-01 08:00', 'gangguan', $budi, '2026-04-01 09:00');        // di luar periode

        $d = app(TicketRecapService::class)->build(2026, 3);

        $this->assertSame(5, $d['summary']['masuk']);
        $this->assertSame(4, $d['summary']['selesai']);       // termasuk tiket dari Februari
        $this->assertSame(1, $d['summary']['dibatalkan']);
        $this->assertSame(1, $d['summary']['terbuka']);

        $rows = collect($d['byStaff'])->keyBy('name');
        $this->assertSame(3, $rows['Budi']['selesai']);
        $this->assertSame(2, $rows['Budi']['masuk']);
        $this->assertSame(1, $rows['Budi']['sla_ok']);
        $this->assertSame(2, $rows['Budi']['sla_late']);     // 48 jam & 48 jam (27 Feb -> 1 Mar)
        $this->assertSame(1, $rows['Andi']['selesai']);
        $this->assertSame(1, $rows['Andi']['selesai_per_tipe']['aktivasi']);
        $this->assertSame(1, $rows['Andi']['terbuka']);
        $this->assertSame(720, $rows['Andi']['avg_minutes']);
        $this->assertSame(0, $rows['Idle Tech']['selesai']);
        $this->assertSame(1, $rows['Belum ditugaskan']['dibatalkan']);
        $this->assertSame('Budi', $d['byStaff'][0]['name'], 'paling produktif di atas');

        $this->assertCount(6, $d['tickets']);  // 5 masuk + 1 selesai dari Februari
    }

    public function test_yearly_ticket_recap_has_month_matrix(): void
    {
        $budi = $this->staff('teknisi', 'Budi');
        $this->ticketAt('2026-01-05 08:00', 'gangguan', $budi, '2026-01-05 09:00');
        $this->ticketAt('2026-06-05 08:00', 'aktivasi', $budi, '2026-06-06 09:00');

        $d = app(TicketRecapService::class)->build(2026);

        $this->assertSame(1, $d['byMonth'][1]['selesai']);
        $this->assertSame(1, $d['byMonth'][6]['masuk_aktivasi']);
        $budiRow = collect($d['staffByMonth'])->firstWhere('name', 'Budi');
        $this->assertSame(1, $budiRow['months'][1]);
        $this->assertSame(1, $budiRow['months'][6]);
        $this->assertSame(2, $budiRow['total']);
    }

    public function test_ticket_recap_pages_and_export_are_admin_only(): void
    {
        $this->ticketAt('2026-03-02 08:00', 'gangguan', null, '2026-03-02 12:00');
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get(route('tickets.recap', ['period' => 'month', 'year' => 2026, 'month' => 3]))
            ->assertOk()->assertSee('Rekap Pekerjaan Tim')->assertSee('Maret 2026');
        $this->actingAs($admin)->get(route('tickets.recap', ['period' => 'year', 'year' => 2026]))
            ->assertOk()->assertSee('Tiket diselesaikan per petugas per bulan');

        $xlsx = $this->actingAs($admin)->get(route('tickets.recap.export', ['period' => 'year', 'year' => 2026]));
        $xlsx->assertOk()->assertDownload('Rekap-Tiket-2026.xlsx');
        $this->assertStringStartsWith('PK', $xlsx->streamedContent(), 'bukan file xlsx (zip)');

        $this->actingAs($this->staff('teknisi'))->get(route('tickets.recap'))->assertForbidden();
    }

    // ---------------------------------------------------------- Rekening koran

    private function account(string $name, float $openingBalance, ?User $owner = null): BankAccount
    {
        return BankAccount::create([
            'bank_name' => $name, 'account_name' => "Rek $name", 'account_number' => '123', 'type' => 'bank',
            'balance' => $openingBalance, 'is_active' => true, 'user_id' => $owner?->id,
        ]);
    }

    private function tx(BankAccount $a, string $type, float $amount, string $date, string $desc): void
    {
        $t = new BankTransaction([
            'bank_account_id' => $a->id, 'type' => $type, 'amount' => $amount,
            'description' => $desc, 'transaction_date' => $date, 'created_by' => $this->actor->id,
        ]);
        $t->skipAutoJournal = true;
        $t->save();
    }

    private User $actor;

    public function test_monthly_statement_has_opening_running_and_closing_balance(): void
    {
        $this->actor = $this->staff('administrator');
        $bca = $this->account('BCA', 1_000_000);                 // saldo pembukaan langsung
        $this->tx($bca, 'deposit', 500_000, '2026-02-20 10:00', 'Feb masuk');
        $this->tx($bca, 'deposit', 200_000, '2026-03-02 09:00', 'Pembayaran INV-1');
        $this->tx($bca, 'withdrawal', 50_000, '2026-03-15 14:00', 'Beli kabel');
        $this->tx($bca, 'deposit', 75_000, '2026-04-01 08:00', 'April masuk');

        $this->assertEquals(1_725_000, (float) $bca->fresh()->balance);

        $st = app(BankStatementService::class)->statement(collect([$bca->fresh()]),
            CarbonImmutable::create(2026, 3, 1), CarbonImmutable::create(2026, 3, 1)->endOfMonth());

        $this->assertEquals(1_500_000, $st['opening']);
        $this->assertEquals(200_000, $st['total_in']);
        $this->assertEquals(50_000, $st['total_out']);
        $this->assertEquals(1_650_000, $st['closing']);
        $this->assertEquals([1_700_000, 1_650_000], $st['rows']->pluck('balance')->all());

        $year = app(BankStatementService::class)->yearSummary(collect([$bca->fresh()]), 2026);
        $this->assertEquals(1_000_000, $year['opening']);
        $this->assertEquals(1_500_000, $year['months'][2]['closing']);
        $this->assertEquals($year['months'][3]['closing'], $year['months'][4]['opening']);
        $this->assertEquals(1_725_000, $year['closing']);     // = saldo di menu Kas & Bank
    }

    public function test_statement_access_follows_account_ownership(): void
    {
        $this->actor = $this->staff('administrator');
        $kasir = $this->staff('kasir');
        $own = $this->account('Kas Kasir', 100_000, $kasir);
        $other = $this->account('BCA', 5_000_000);
        $this->tx($own, 'deposit', 25_000, now()->startOfMonth()->addDay()->toDateTimeString(), 'Setoran pelanggan');

        $this->actingAs($kasir)->get(route('bank-statement.index'))
            ->assertOk()->assertSee('Setoran pelanggan')->assertDontSee('Semua rekening (gabungan)');
        $this->actingAs($kasir)->get(route('bank-statement.index', ['account' => $other->id]))->assertForbidden();
        $this->actingAs($kasir)->get(route('bank-statement.index', ['account' => 'all']))
            ->assertOk()->assertDontSee('Rek BCA');

        $this->actingAs($this->actor)->get(route('bank-statement.index', ['account' => 'all', 'period' => 'year']))
            ->assertOk()->assertSee('Semua rekening (2 rekening, gabungan)');

        $xlsx = $this->actingAs($this->actor)->get(route('bank-statement.export', ['account' => 'all', 'period' => 'year', 'year' => now()->year]));
        $xlsx->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
    }
}
