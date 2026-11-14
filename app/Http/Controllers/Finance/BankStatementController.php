<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\Reports\BankStatementService;
use App\Support\Period;
use App\Support\SpreadsheetExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rekening koran Kas & Bank (bulanan / tahunan).
 *
 * Hak akses sama dengan detail rekening: administrator melihat semua rekening
 * (termasuk gabungan), staf lain hanya rekening miliknya sendiri.
 */
class BankStatementController extends Controller
{
    public function __construct(private BankStatementService $statements)
    {
    }

    public function index(Request $request)
    {
        $available = $this->availableAccounts();
        if ($available->isEmpty()) {
            return redirect()->route('bank-accounts.index')->with('error', 'Belum ada rekening Kas & Bank yang bisa Anda lihat.');
        }

        [$selected, $accounts] = $this->selectedAccounts($request, $available);
        [$year, $month] = Period::fromRequest($request);

        $data = [
            'available' => $available,
            'selected' => $selected,
            'accounts' => $accounts,
            'year' => $year,
            'month' => $month,
            'years' => Period::years(BankTransaction::whereIn('bank_account_id', $accounts->pluck('id'))->min('transaction_date')),
            'periodLabel' => Period::label($year, $month),
            'accountLabel' => $this->accountLabel($selected, $accounts),
            'months' => Period::MONTHS,
        ];

        if ($month) {
            $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
            $data['statement'] = $this->statements->statement($accounts, $start, $start->endOfMonth());
        } else {
            $data['summary'] = $this->statements->yearSummary($accounts, $year);
        }

        return view('finance.statement', $data);
    }

    public function export(Request $request)
    {
        $available = $this->availableAccounts();
        abort_if($available->isEmpty(), 404);

        [$selected, $accounts] = $this->selectedAccounts($request, $available);
        [$year, $month] = Period::fromRequest($request);
        $accountLabel = $this->accountLabel($selected, $accounts);
        $multi = $selected === 'all';
        $months = Period::MONTHS;

        $xlsx = new SpreadsheetExport((string) get_setting('company_name', config('app.name')));

        if (! $month) {
            $sum = $this->statements->yearSummary($accounts, $year);
            $xlsx->sheet('Ringkasan ' . $year, ["Rekening Koran Tahunan $year", $accountLabel],
                ['Bulan', 'Saldo awal', 'Masuk (CR)', 'Keluar (DB)', 'Saldo akhir', 'Jumlah transaksi'],
                array_map(fn ($m, $r) => [$months[$m], $r['opening'], $r['total_in'], $r['total_out'], $r['closing'], $r['count']], array_keys($sum['months']), $sum['months']),
                [1 => 'money', 2 => 'money', 3 => 'money', 4 => 'money', 5 => 'int'],
                ['Total ' . $year, $sum['opening'], $sum['total_in'], $sum['total_out'], $sum['closing'], $sum['count']],
            );
        }

        // Bulanan: satu sheet. Tahunan: satu sheet per bulan (sampai bulan berjalan).
        $monthList = $month ? [$month] : array_filter(range(1, 12), fn ($m) => ! CarbonImmutable::create($year, $m, 1)->isFuture());
        foreach ($monthList as $m) {
            $start = CarbonImmutable::create($year, $m, 1)->startOfMonth();
            $st = $this->statements->statement($accounts, $start, $start->endOfMonth());
            $this->statementSheet($xlsx, $month ? 'Mutasi' : $months[$m], $st, $months[$m] . ' ' . $year, $accountLabel, $multi);
        }

        $slug = $multi ? 'Semua-Rekening' : preg_replace('/[^A-Za-z0-9]+/', '-', $accounts->first()->bank_name . '-' . ($accounts->first()->account_number ?: $accounts->first()->account_name));
        $file = 'Rekening-Koran-' . trim($slug, '-') . '-' . ($month ? sprintf('%d-%02d', $year, $month) : $year) . '.xlsx';

        return $xlsx->download($file);
    }

    private function statementSheet(SpreadsheetExport $xlsx, string $name, array $st, string $periodLabel, string $accountLabel, bool $multi): void
    {
        $headers = array_merge(['Tanggal'], $multi ? ['Rekening'] : [], ['Keterangan', 'Referensi', 'Kategori', 'Masuk (CR)', 'Keluar (DB)', 'Saldo']);
        $off = $multi ? 1 : 0;
        $blank = array_fill(0, $off, null);

        $rows = [array_merge([$st['start']], $blank, ['SALDO AWAL', null, null, null, null, $st['opening']])];
        foreach ($st['rows'] as $r) {
            $rows[] = array_merge([$r['date']], $multi ? [$r['account']] : [], [$r['description'], $r['reference'], $r['category'], $r['in'] ?: null, $r['out'] ?: null, $r['balance']]);
        }

        $xlsx->sheet($name,
            ["Rekening Koran — $periodLabel", $accountLabel,
             sprintf('Saldo awal %s · Masuk %s (%d trx) · Keluar %s (%d trx) · Saldo akhir %s',
                 rupiah($st['opening'], 0, false), rupiah($st['total_in'], 0, false), $st['count_in'],
                 rupiah($st['total_out'], 0, false), $st['count_out'], rupiah($st['closing'], 0, false))],
            $headers,
            $rows,
            [0 => 'datetime', 4 + $off => 'money', 5 + $off => 'money', 6 + $off => 'money'],
            array_merge(['SALDO AKHIR'], $blank, [null, null, null, $st['total_in'], $st['total_out'], $st['closing']]),
            array_merge([17], $multi ? [26] : [], [48, 18, 24, 16, 16, 18]),
        );
    }

    /** Rekening yang boleh dilihat user ini. */
    private function availableAccounts(): Collection
    {
        $user = auth()->user();
        $query = BankAccount::orderBy('bank_name')->orderBy('account_name');
        if (! $user->isAdministrator()) {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    /** @return array{0: string|int, 1: Collection<int, BankAccount>} */
    private function selectedAccounts(Request $request, Collection $available): array
    {
        $param = $request->query('account');

        if ($param === 'all' && auth()->user()->isAdministrator()) {
            return ['all', $available];
        }
        // "all" untuk non-administrator jatuh ke rekening miliknya sendiri.
        if ($param !== null && $param !== '' && $param !== 'all') {
            $account = $available->firstWhere('id', (int) $param);
            abort_unless($account, 403, 'Anda tidak memiliki akses ke rekening ini.');

            return [$account->id, collect([$account])];
        }

        $first = $available->first();

        return [$first->id, collect([$first])];
    }

    private function accountLabel(string|int $selected, Collection $accounts): string
    {
        if ($selected === 'all') {
            return 'Semua rekening (' . $accounts->count() . ' rekening, gabungan)';
        }
        $a = $accounts->first();

        return trim($a->bank_name . ' · ' . $a->account_name . ($a->account_number ? ' · No. ' . $a->account_number : ''));
    }
}
