<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\Journal;
use App\Models\User;
use App\Services\Finance\DepreciationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Penyusutan aset tetap: nominal, mulai bulan perolehan, sisa terakhir, sekali per bulan, tutup buku, akses. */
class DepreciationTest extends TestCase
{
    use RefreshDatabase;

    private array $acc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00'));
        foreach (['1202' => ['Peralatan Jaringan', 'asset'], '1299' => ['Akumulasi Penyusutan', 'asset'], '5201' => ['Beban Penyusutan', 'expense']] as $code => [$name, $type]) {
            $this->acc[$code] = ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type])->id;
        }
    }

    private function asset(string $code, string $date, float $price, int $months, float $acc = 0, string $status = 'active'): FixedAsset
    {
        return FixedAsset::create(['name' => "Aset {$code}", 'asset_code' => $code, 'purchase_date' => $date, 'purchase_price' => $price,
            'salvage_value' => 0, 'useful_life_months' => $months, 'accumulated_depreciation' => $acc, 'status' => $status,
            'asset_account_id' => $this->acc['1202'], 'depreciation_account_id' => $this->acc['5201'], 'accumulated_account_id' => $this->acc['1299']]);
    }

    public function test_plan_and_run_post_balanced_journals_once(): void
    {
        $olt = $this->asset('OLT-1', '2026-03-10', 36000000, 36);            // 1.000.000 / bulan
        $last = $this->asset('RTR-1', '2025-01-05', 1200000, 12, 1150000);   // sisa 50.000 < 100.000
        $future = $this->asset('SPL-1', '2026-12-01', 600000, 12);           // dibeli bulan depan
        $sold = $this->asset('OLD-1', '2024-01-01', 600000, 12, 0, 'disposed');
        $wrong = $this->asset('BAD-1', '2026-01-01', 1200000, 12);
        $wrong->update(['accumulated_account_id' => $this->acc['1202']]);   // akumulasi = akun aset
        $svc = app(DepreciationService::class);

        $plan = collect($svc->plan(CarbonImmutable::parse('2026-11-01')))->keyBy(fn ($r) => $r['asset']->asset_code);
        $this->assertSame(['due', 1000000.0], [$plan['OLT-1']['status'], $plan['OLT-1']['amount']]);
        $this->assertSame(['due', 50000.0], [$plan['RTR-1']['status'], $plan['RTR-1']['amount']]);
        $this->assertSame('not_yet', $plan['SPL-1']['status']);
        $this->assertSame('inactive', $plan['OLD-1']['status']);
        $this->assertSame('config', $plan['BAD-1']['status']);

        $this->assertSame(['count' => 2, 'total' => 1050000.0], $svc->run(CarbonImmutable::parse('2026-11-01')));
        $this->assertSame(['count' => 0, 'total' => 0.0], $svc->run(CarbonImmutable::parse('2026-11-01')));   // tidak dobel
        $this->assertSame(1000000.0, (float) $olt->fresh()->accumulated_depreciation);
        $this->assertSame(1200000.0, (float) $last->fresh()->accumulated_depreciation);   // habis, tidak lebih

        $j = Journal::where('reference', 'Depresiasi Aset: OLT-1')->with('items')->firstOrFail();
        $this->assertSame('2026-11-30', $j->date->toDateString());
        $this->assertEquals(1000000, $j->items->sum('debit'));
        $this->assertEquals(1000000, $j->items->sum('credit'));
        $this->assertEquals(1000000, $j->items->firstWhere('account_id', $this->acc['5201'])->debit);
        $this->assertEquals(1000000, $j->items->firstWhere('account_id', $this->acc['1299'])->credit);

        // Bulan sebelum perolehan: belum disusutkan
        $this->assertSame('not_yet', collect($svc->plan(CarbonImmutable::parse('2026-02-01')))->firstWhere('asset.asset_code', 'OLT-1')['status']);
        // Bulan terlewat bisa dikejar
        $this->assertSame(1, $svc->run(CarbonImmutable::parse('2026-10-01'))['count']);
        $this->assertSame('done', collect($svc->plan(CarbonImmutable::parse('2026-12-01')))->firstWhere('asset.asset_code', 'RTR-1')['status']);
    }

    public function test_closed_period_is_skipped_and_command_works(): void
    {
        $this->asset('OLT-2', '2026-01-01', 12000000, 12);
        \App\Models\AccountingPeriod::create(['month' => 9, 'year' => 2026, 'is_closed' => true, 'closed_at' => now()]);
        $svc = app(DepreciationService::class);
        $this->assertSame('closed', $svc->plan(CarbonImmutable::parse('2026-09-01'))[0]['status']);
        $this->assertSame(0, $svc->run(CarbonImmutable::parse('2026-09-01'))['count']);

        $this->artisan('app:calculate-depreciation --month=2026-11 --dry-run')->expectsOutputToContain('Siap dijurnal')->assertSuccessful();
        $this->assertSame(0, Journal::count());
        $this->artisan('app:calculate-depreciation --month=2026-11')->expectsOutputToContain('1 aset dijurnal')->assertSuccessful();
        $this->assertTrue(ActivityLog::where('action', 'depreciation_posted')->exists());
        $this->artisan('app:calculate-depreciation --month=11-2026')->assertFailed();
    }

    public function test_page_toggle_manual_run_and_access(): void
    {
        $this->asset('OLT-3', '2026-01-01', 12000000, 12);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->assertFalse(DepreciationService::enabled());   // bawaan mati

        $this->actingAs($admin)->get(route('fixed-assets.index', ['month' => '2026-11']))->assertOk()
            ->assertSee('Penyusutan bulanan')->assertSee('Otomatis nonaktif')->assertSee('Jurnal penyusutan November 2026')->assertDontSee('Useful Life');
        $this->post(route('fixed-assets.depreciation.auto'), ['enabled' => 1])->assertSessionHas('success');
        $this->assertTrue(DepreciationService::enabled());
        $this->post(route('fixed-assets.depreciation.run'), ['month' => '2026-11'])->assertSessionHas('success', fn ($m) => str_contains($m, '1 aset dijurnal'));
        $this->post(route('fixed-assets.depreciation.run'), ['month' => '2026-12'])->assertSessionHas('error');   // bulan belum berjalan

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('fixed-assets.index'))->assertOk()->assertDontSee('Aktifkan otomatis');
        $this->post(route('fixed-assets.depreciation.run'), ['month' => '2026-10'])->assertForbidden();
    }
}
