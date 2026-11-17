<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Models\User;
use App\Services\Finance\BankReconciliationService as Svc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Import mutasi rekening (CSV bank) & rekonsiliasi dengan Kas & Bank. */
class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private BankAccount $account;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = BankAccount::create(['bank_name' => 'BCA', 'account_name' => 'PT Uji', 'account_number' => '123', 'type' => 'bank', 'balance' => 0, 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function tx(string $type, float $amount, string $date, string $desc = 'x'): BankTransaction
    {
        return BankTransaction::create(['bank_account_id' => $this->account->id, 'type' => $type, 'amount' => $amount,
            'transaction_date' => $date, 'description' => $desc, 'status' => 'completed']);
    }

    /** Format mirip unduhan BCA: info rekening di atas, Jumlah dengan CR/DB, baris saldo awal & total di bawah. */
    private function bcaCsv(): string
    {
        return implode("\n", [
            'No. rekening : 1234567890',
            'Nama : PT UJI',
            'Periode : 01/11/2026 - 30/11/2026',
            '',
            'Tanggal Transaksi,Keterangan,Cabang,Jumlah,Saldo',
            '01/11,SALDO AWAL,0000,,"1,000,000.00"',
            '03/11,TRSF E-BANKING CR 0311/FTSCY/WS95031 PELANGGAN BUDI,0998,"150,000.00 CR","1,150,000.00"',
            '05/11,BIAYA ADM,0000,"10,000.00 DB","1,140,000.00"',
            '06/11,TRSF E-BANKING DB BAYAR VENDOR KABEL,0998,"500,000.00 DB","640,000.00"',
            '07/11,TRSF E-BANKING CR SETORAN TUNAI,0998,"150,000.00 CR","790,000.00"',
            'Saldo Awal,"1,000,000.00"',
            'Mutasi Kredit,"300,000.00"',
        ]);
    }

    public function test_amount_and_date_parsing(): void
    {
        $cases = ['1.234.567,50' => 1234567.5, '1,234,567.50' => 1234567.5, '1234567' => 1234567.0, '150.000' => 150000.0,
            '150,000' => 150000.0, '(15.000)' => -15000.0, '-15.000,00' => -15000.0, 'Rp 2.500' => 2500.0, '12,5' => 12.5];
        foreach ($cases as $in => $out) {
            $this->assertSame($out, Svc::parseAmount($in)['value'], $in);
        }
        $this->assertSame(['value' => 150000.0, 'marker' => 'CR'], Svc::parseAmount('150,000.00 CR'));
        $this->assertSame('DB', Svc::parseAmount('10.000 DB')['marker']);
        $this->assertNull(Svc::parseAmount(''));

        $this->assertSame('2026-11-03', Svc::parseDate('03/11/2026')->toDateString());
        $this->assertSame('2026-11-03', Svc::parseDate('03/11', 'auto', 2026)->toDateString());
        $this->assertSame('2026-11-03', Svc::parseDate('2026-11-03 10:15:00')->toDateString());
        $this->assertSame('2026-08-17', Svc::parseDate('17 Agu 2026')->toDateString());
        $this->assertSame('2026-08-17', Svc::parseDate('17-Aug-26')->toDateString());
        $this->assertSame('2026-03-11', Svc::parseDate('03/11/2026', 'm/d/Y')->toDateString());
        $this->assertSame('2026-12-25', Svc::parseDate('12/25/2026')->toDateString());   // otomatis: hari > 12 di posisi kedua
        $this->assertNull(Svc::parseDate('SALDO AWAL'));
    }

    public function test_bca_import_auto_matches_and_explains_difference(): void
    {
        $budi = $this->tx('deposit', 150000, '2026-11-03 09:00', 'Pembayaran invoice Budi');
        $vendor = $this->tx('withdrawal', 500000, '2026-11-04 15:00', 'Bayar kabel');   // dicatat 2 hari lebih awal
        $wrong = $this->tx('deposit', 75000, '2026-11-05 10:00', 'Tercatat tapi tidak ada di bank');
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->createWithContent('mutasi-bca.csv', $this->bcaCsv());
        $this->post(route('bank-reconciliation.upload'), ['bank_account_id' => $this->account->id, 'file' => $file])
            ->assertRedirect(route('bank-reconciliation.map'));

        // Pemetaan ditebak dari judul kolom
        $res = $this->get(route('bank-reconciliation.map'))->assertOk()->assertSee('mutasi terbaca');
        $this->assertSame(4, $res->viewData('previewCount'));
        $map = $res->viewData('map');
        $this->assertSame([3, 0, 1, 'single', 3, 4], [$map['header_row'], $map['date_col'], $map['desc_col'], $map['amount_mode'], $map['amount_col'], $map['balance_col']]);

        $this->post(route('bank-reconciliation.confirm'), $map + ['year' => 2026, 'action' => 'import'])->assertSessionHas('success');
        $this->assertSame(4, BankStatementLine::count());
        $this->assertNotNull(\App\Models\Setting::where('key', 'bank_statement_map_' . $this->account->id)->value('value'));   // diingat

        $lines = BankStatementLine::orderBy('line_date')->orderBy('id')->get();
        $this->assertSame($budi->id, $lines[0]->bank_transaction_id);
        $this->assertSame('unmatched', $lines[1]->status);           // biaya admin belum dicatat
        $this->assertSame($vendor->id, $lines[2]->bank_transaction_id);   // ±3 hari
        $this->assertSame('unmatched', $lines[3]->status);           // 150.000 kedua: transaksi Budi sudah dipakai (1:1)

        $page = $this->get(route('bank-reconciliation.index', ['account' => $this->account->id, 'from' => '2026-11-01', 'to' => '2026-11-30']))->assertOk();
        $s = $page->viewData('summary');
        $this->assertSame(1000000.0, $s['bank_opening']);
        $this->assertSame(790000.0, $s['bank_closing']);
        $this->assertSame([$wrong->id], $s['only_app']->pluck('id')->all());
        $this->assertCount(2, $s['only_bank']);
        $page->assertSee('BIAYA ADM')->assertSee('Tercatat tapi tidak ada di bank');

        // Import ulang file yang sama: tidak ada duplikat
        $this->post(route('bank-reconciliation.upload'), ['bank_account_id' => $this->account->id, 'file' => UploadedFile::fake()->createWithContent('mutasi-bca.csv', $this->bcaCsv())]);
        $this->post(route('bank-reconciliation.confirm'), $map + ['year' => 2026, 'action' => 'import'])->assertSessionHas('success', fn ($m) => str_contains($m, '0 baris mutasi baru'));
        $this->assertSame(4, BankStatementLine::count());
    }

    public function test_split_debit_credit_columns_and_manual_actions(): void
    {
        $csv = "Date;Description;Debit;Credit;Balance\n2026-11-02;Transfer masuk Siti;0;250.000,00;1.250.000,00\n2026-11-09;Tarik tunai;100.000,00;0;1.150.000,00\n";
        $svc = app(Svc::class);
        $path = tempnam(sys_get_temp_dir(), 'st') . '.csv';
        file_put_contents($path, $csv);
        $rows = $svc->readRows($path);
        $map = $svc->guessMapping($rows);
        $this->assertSame('split', $map['amount_mode']);
        $built = $svc->buildLines($rows, $map);
        $this->assertSame([250000.0, -100000.0], array_column($built['lines'], 'amount'));
        $import = $svc->import($this->account, $built['lines'], 'mandiri.csv', $this->admin);
        $this->assertSame(1000000.0, (float) $import->opening_balance);

        $this->actingAs($this->admin);
        [$in, $out] = BankStatementLine::orderBy('line_date')->get()->all();

        // Cocokkan manual dengan transaksi yang nominal/tanggalnya berbeda
        $siti = $this->tx('deposit', 250000, '2026-11-20 10:00', 'Siti (dicatat terlambat)');
        $this->getJson(route('bank-reconciliation.candidates', $in))->assertOk()->assertJsonFragment(['id' => $siti->id]);
        $this->post(route('bank-reconciliation.match', $in), ['bank_transaction_id' => $siti->id])->assertSessionHas('success');
        $this->assertSame(['matched', 'manual'], [$in->fresh()->status, $in->fresh()->match_type]);
        $this->post(route('bank-reconciliation.match', $out), ['bank_transaction_id' => $siti->id])->assertSessionHas('error');   // sudah dipakai

        // Catat di aplikasi: form pengeluaran terisi dari mutasi
        $this->get(route('bank-reconciliation.record', $out))->assertRedirect(route('bank-transactions.expense'))
            ->assertSessionHasInput('amount', '100000.00')->assertSessionHasInput('description', 'Tarik tunai');

        $this->post(route('bank-reconciliation.ignore', $out), ['note' => 'Diambil pemilik'])->assertSessionHas('success');
        $this->assertSame('ignored', $out->fresh()->status);
        $this->post(route('bank-reconciliation.unmatch', $out))->assertSessionHas('success');
        $this->assertSame('unmatched', $out->fresh()->status);

        $this->delete(route('bank-reconciliation.import.destroy', $import))->assertSessionHas('success');
        $this->assertSame(0, BankStatementLine::count());
        $this->assertTrue(BankTransaction::whereKey($siti->id)->exists());   // transaksi aplikasi tidak ikut terhapus
    }

    public function test_access(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);
        $this->actingAs($kasir)->get(route('bank-reconciliation.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('bank-reconciliation.index'))->assertOk()->assertSee('Rekonsiliasi Bank');
    }
}
