<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laporan arus kas: jurnal yang dipecah ke beberapa akun lawan tidak boleh dihitung ganda,
 * saldo akhir = saldo kas sebenarnya, dan ekspor Excel berisi angka (bukan "...").
 */
class CashFlowReportTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $code, string $name, string $type, ?int $parent = null): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'parent_id' => $parent, 'is_active' => true]);
    }

    private function journal(string $date, array $lines): void
    {
        $j = Journal::create(['journal_number' => 'J-' . uniqid(), 'date' => $date, 'reference' => 'T-' . uniqid(), 'description' => 'uji', 'status' => 'posted']);
        foreach ($lines as [$account, $debit, $credit]) {
            JournalItem::create(['journal_id' => $j->id, 'account_id' => $account->id, 'debit' => $debit, 'credit' => $credit]);
        }
    }

    public function test_split_journal_is_not_double_counted_and_closing_matches_cash_balance(): void
    {
        $cash = $this->account('1001', 'Kas & Bank', 'asset');
        $kas = $this->account('1101', 'Kas Kantor', 'asset', $cash->id);
        $income = $this->account('4101', 'Pendapatan Langganan', 'income');
        $vat = $this->account('2103', 'Hutang PPN', 'liability');
        $expense = $this->account('5101', 'Beban Operasional', 'expense');
        $prepaid = $this->account('1190', 'Uang Muka Lain', 'asset');   // akun di luar daftar klasifikasi

        $this->journal('2026-02-28', [[$kas, 500000, 0], [$income, 0, 500000]]);              // saldo awal
        // Pembayaran 111.000 = pendapatan 100.000 + PPN 11.000 (satu jurnal, dua akun lawan)
        $this->journal('2026-03-05', [[$kas, 111000, 0], [$income, 0, 100000], [$vat, 0, 11000]]);
        $this->journal('2026-03-10', [[$expense, 40000, 0], [$kas, 0, 40000]]);
        $this->journal('2026-03-12', [[$prepaid, 25000, 0], [$kas, 0, 25000]]);               // tidak terklasifikasi

        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $view = $this->actingAs($admin)->get(route('accounting.reports.cash-flow', ['start_date' => '2026-03-01', 'end_date' => '2026-03-31']))
            ->assertOk()->viewData('operatingIn');
        $this->assertEquals(100000, $view, 'kode lama: seluruh kas 111.000 di operasional dan 111.000 lagi di pendanaan (dari akun PPN)');

        $res = $this->actingAs($admin)->get(route('accounting.reports.cash-flow', ['start_date' => '2026-03-01', 'end_date' => '2026-03-31']));
        $this->assertEquals(11000, $res->viewData('financingIn'));
        $this->assertEquals(40000, $res->viewData('operatingOut'));
        $this->assertEquals(-25000, $res->viewData('netOther'));
        $this->assertEquals(500000, $res->viewData('openingCash'));
        $this->assertEquals(546000, $res->viewData('closingCash'));
        $this->assertEquals($res->viewData('closingCash'), $res->viewData('openingCash') + $res->viewData('netIncrease'));
        $res->assertSee('Belum terklasifikasi');

        $xlsx = $this->actingAs($admin)->get(route('accounting.reports.cash-flow.export', ['start_date' => '2026-03-01', 'end_date' => '2026-03-31']));
        $xlsx->assertOk()->assertDownload('Arus-Kas-2026-03-01_2026-03-31.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'cf') . '.xlsx';
        file_put_contents($path, $xlsx->streamedContent());
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        $values = collect($sheet->toArray(null, false, false))->flatten()->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v)->all();
        @unlink($path);
        $this->assertContains(100000.0, $values);
        $this->assertContains(546000.0, $values);
    }

    public function test_invoice_collections_are_operating_and_codes_are_configurable(): void
    {
        $cash = $this->account('1001', 'Kas & Bank', 'asset');
        $bank = $this->account('1102', 'Bank', 'asset', $cash->id);
        $receivable = $this->account('1104', 'Piutang Pelanggan', 'asset');
        $stock = $this->account('1105', 'Persediaan Barang', 'asset');
        $equipment = $this->account('1202', 'Peralatan Jaringan', 'asset');

        // Pembayaran invoice: Dr Bank / Cr Piutang (dulu masuk "lainnya", bukan operasional)
        $this->journal('2026-04-03', [[$bank, 150000, 0], [$receivable, 0, 150000]]);
        $this->journal('2026-04-04', [[$stock, 60000, 0], [$bank, 0, 60000]]);         // beli stok tunai
        $this->journal('2026-04-05', [[$equipment, 90000, 0], [$bank, 0, 90000]]);     // beli OLT

        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $range = ['start_date' => '2026-04-01', 'end_date' => '2026-04-30'];
        $res = $this->actingAs($admin)->get(route('accounting.reports.cash-flow', $range))->assertOk()->assertSee('Pengaturan klasifikasi akun');
        $this->assertEquals(150000, $res->viewData('operatingIn'));
        $this->assertEquals(60000, $res->viewData('operatingOut'));
        $this->assertEquals(90000, $res->viewData('investingOut'));
        $this->assertEquals(0, $res->viewData('netOther'));

        // API memakai perhitungan yang sama
        \Laravel\Sanctum\Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/accounting/cash-flow?' . http_build_query($range))->assertOk()
            ->assertJsonPath('operating_in', 150000)->assertJsonPath('investing_out', 90000);

        // Ubah klasifikasi: persediaan dianggap investasi
        $this->post(route('accounting.reports.cash-flow.settings'), [
            'cashflow_operating_in_codes' => '1104', 'cashflow_operating_out_codes' => '2101', 'cashflow_investing_codes' => '1105, 1202',
        ])->assertSessionHas('success');
        $res = $this->get(route('accounting.reports.cash-flow', $range));
        $this->assertEquals(150000, $res->viewData('investingOut'));
        $this->assertEquals(0, $res->viewData('operatingOut'));

        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]))
            ->post(route('accounting.reports.cash-flow.settings'), ['cashflow_investing_codes' => ''])->assertForbidden();
    }
}
