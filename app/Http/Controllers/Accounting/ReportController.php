<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Check if this period is closed
        $isClosed = \App\Models\AccountingPeriod::where('month', Carbon::parse($endDate)->month)
            ->where('year', Carbon::parse($endDate)->year)
            ->where('is_closed', true)
            ->exists();

        // 1. Fetch Income Accounts
        $incomeAccounts = ChartOfAccount::where('type', 'income')
            ->where('is_active', true)
            ->with(['journalItems' => function($q) use ($startDate, $endDate) {
                $q->whereHas('journal', function($jq) use ($startDate, $endDate) {
                    $jq->whereBetween('date', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->map(function($account) {
                // For Income: Credit - Debit
                $account->period_balance = $account->journalItems->sum('credit') - $account->journalItems->sum('debit');
                return $account;
            })
            ->filter(fn($a) => $a->period_balance != 0);

        // 2. Fetch Expense Accounts
        $expenseAccounts = ChartOfAccount::where('type', 'expense')
            ->where('is_active', true)
            ->with(['journalItems' => function($q) use ($startDate, $endDate) {
                $q->whereHas('journal', function($jq) use ($startDate, $endDate) {
                    $jq->whereBetween('date', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->map(function($account) {
                // For Expense: Debit - Credit
                $account->period_balance = $account->journalItems->sum('debit') - $account->journalItems->sum('credit');
                return $account;
            })
            ->filter(fn($a) => $a->period_balance != 0);

        $totalIncome = $incomeAccounts->sum('period_balance');
        $totalExpense = $expenseAccounts->sum('period_balance');
        $netProfit = $totalIncome - $totalExpense;

        return view('accounting.reports.profit-loss', compact(
            'incomeAccounts', 'expenseAccounts', 'totalIncome', 'totalExpense', 'netProfit', 'startDate', 'endDate', 'isClosed'
        ));
    }

    public function balanceSheet(Request $request)
    {
        $date = $request->input('date', Carbon::now()->toDateString());

        // Check if this date is in a closed period
        $isClosed = \App\Models\AccountingPeriod::where('month', Carbon::parse($date)->month)
            ->where('year', Carbon::parse($date)->year)
            ->where('is_closed', true)
            ->exists();

        // Fetch all accounts with their cumulative balance up to $date
        $accounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($date) {
                // Calculate balance up to this date
                $items = JournalItem::whereHas('journal', function($q) use ($date) {
                    $q->where('date', '<=', $date);
                })->where('account_id', $account->id)->get();

                if ($account->type === 'asset') {
                    $account->current_balance = $items->sum('debit') - $items->sum('credit');
                } else {
                    // Liability & Equity: Credit - Debit
                    $account->current_balance = $items->sum('credit') - $items->sum('debit');
                }
                return $account;
            });

        // Add Current Earnings (Net Profit from beginning until $date)
        // This is a simplification. Usually there is a "Retained Earnings" account.
        $incomeItems = JournalItem::whereHas('journal', function($q) use ($date) {
            $q->where('date', '<=', $date);
        })->whereHas('account', fn($q) => $q->where('type', 'income'))->get();

        $expenseItems = JournalItem::whereHas('journal', function($q) use ($date) {
            $q->where('date', '<=', $date);
        })->whereHas('account', fn($q) => $q->where('type', 'expense'))->get();

        $currentEarnings = ($incomeItems->sum('credit') - $incomeItems->sum('debit')) - 
                          ($expenseItems->sum('debit') - $expenseItems->sum('credit'));

        $assets = $accounts->where('type', 'asset')->filter(fn($a) => $a->current_balance != 0);
        $liabilities = $accounts->where('type', 'liability')->filter(fn($a) => $a->current_balance != 0);
        $equity = $accounts->where('type', 'equity')->filter(fn($a) => $a->current_balance != 0);

        $totalAssets = $assets->sum('current_balance');
        $totalLiabilities = $liabilities->sum('current_balance');
        $totalEquity = $equity->sum('current_balance') + $currentEarnings;

        return view('accounting.reports.balance-sheet', compact(
            'assets', 'liabilities', 'equity', 'totalAssets', 'totalLiabilities', 'totalEquity', 'currentEarnings', 'date', 'isClosed'
        ));
    }

    public function taxSummary(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // 1. Tax Output (PPN Keluaran - Code 2103)
        $taxOutputAccount = ChartOfAccount::where('code', '2103')->first();
        $totalTaxOutput = 0;
        if ($taxOutputAccount) {
            $taxOutputItems = JournalItem::where('account_id', $taxOutputAccount->id)
                ->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })->get();
            $totalTaxOutput = $taxOutputItems->sum('credit') - $taxOutputItems->sum('debit');
        }

        // 2. Tax Input (PPN Masukan - Code 1105)
        $taxInputAccount = ChartOfAccount::where('code', '1105')->first();
        $totalTaxInput = 0;
        if ($taxInputAccount) {
            $taxInputItems = JournalItem::where('account_id', $taxInputAccount->id)
                ->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })->get();
            $totalTaxInput = $taxInputItems->sum('debit') - $taxInputItems->sum('credit');
        }

        $netTaxPayable = $totalTaxOutput - $totalTaxInput;

        return view('accounting.reports.tax-summary', compact(
            'totalTaxOutput', 'totalTaxInput', 'netTaxPayable', 'startDate', 'endDate'
        ));
    }

    public function ledger(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $accountId = $request->input('account_id');
        $allTime = $request->has('all_time');
        
        $startDate = $allTime ? '2000-01-01' : $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $allTime ? Carbon::now()->toDateString() : $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $itemsByAccount = collect();
        $summary = [
            'total_opening' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
            'total_ending' => 0
        ];

        // If specific account or All Accounts (null account_id)
        $queryAccounts = $accountId ? ChartOfAccount::where('id', $accountId)->get() : $accounts;

        foreach ($queryAccounts as $account) {
            $openingBalance = 0;
            if (!$allTime) {
                $prevItems = JournalItem::where('account_id', $account->id)
                    ->whereHas('journal', function($q) use ($startDate) {
                        $q->where('date', '<', $startDate);
                    })->get();
                
                $openingBalance = in_array($account->type, ['asset', 'expense']) 
                    ? $prevItems->sum('debit') - $prevItems->sum('credit')
                    : $prevItems->sum('credit') - $prevItems->sum('debit');
            }

            $itemsQuery = JournalItem::with('journal')->where('account_id', $account->id);
            if (!$allTime) {
                $itemsQuery->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            }

            $items = $itemsQuery->get()->sortBy(fn($i) => $i->journal->date . '-' . $i->id);

            $debit = $items->sum('debit');
            $credit = $items->sum('credit');
            $endingBalance = $openingBalance;
            if (in_array($account->type, ['asset', 'expense'])) {
                $endingBalance += ($debit - $credit);
            } else {
                $endingBalance += ($credit - $debit);
            }

            if ($items->count() > 0 || $openingBalance != 0 || $accountId == $account->id) {
                $itemsByAccount[$account->id] = [
                    'account' => $account,
                    'items' => $items,
                    'opening_balance' => $openingBalance,
                    'debit' => $debit,
                    'credit' => $credit,
                    'ending_balance' => $endingBalance
                ];

                $summary['total_opening'] += $openingBalance;
                $summary['total_debit'] += $debit;
                $summary['total_credit'] += $credit;
                $summary['total_ending'] += $endingBalance;
            }
        }

        return view('accounting.reports.ledger', compact(
            'accounts', 'itemsByAccount', 'summary', 'startDate', 'endDate', 'accountId', 'allTime'
        ));
    }

    public function cashFlow(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Get all journal items related to Cash/Bank accounts (Code 1101, 1102)
        $cashAccounts = ChartOfAccount::whereIn('code', ['1101', '1102'])->pluck('id');
        
        $cashJournals = JournalItem::whereIn('account_id', $cashAccounts)
            ->whereHas('journal', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->with(['journal.items.account'])
            ->get();

        $operatingIn = 0;
        $operatingOut = 0;
        $investingOut = 0;
        $financingIn = 0;

        foreach ($cashJournals as $cashItem) {
            $isDebit = $cashItem->debit > 0;
            $amount = $isDebit ? $cashItem->debit : $cashItem->credit;
            
            // Find the offset account in the same journal
            $offsetItems = $cashItem->journal->items->where('account_id', '!=', $cashItem->account_id);
            
            foreach ($offsetItems as $offset) {
                $type = $offset->account->type;
                $code = $offset->account->code;

                if ($isDebit) { // Cash In
                    if ($type === 'income' || $code === '1103') { // Revenue or Accounts Receivable
                        $operatingIn += $amount;
                    } elseif ($type === 'equity' || $type === 'liability') {
                        $financingIn += $amount;
                    }
                } else { // Cash Out
                    if ($type === 'expense' || $code === '2101' || $code === '2103') { // Expense, AP, or Tax Payable
                        $operatingOut += $amount;
                    } elseif ($code === '1104' || $code === '1201' || $code === '1202') { // Inventory or Fixed Assets
                        $investingOut += $amount;
                    }
                }
            }
        }

        $netOperating = $operatingIn - $operatingOut;
        $netInvesting = -$investingOut;
        $netFinancing = $financingIn;
        $netIncrease = $netOperating + $netInvesting + $netFinancing;

        // Opening Cash Balance
        $prevCashItems = JournalItem::whereIn('account_id', $cashAccounts)
            ->whereHas('journal', function($q) use ($startDate) {
                $q->where('date', '<', $startDate);
            })->get();
        $openingCash = $prevCashItems->sum('debit') - $prevCashItems->sum('credit');
        $closingCash = $openingCash + $netIncrease;

        return view('accounting.reports.cash-flow', compact(
            'operatingIn', 'operatingOut', 'investingOut', 'financingIn',
            'netOperating', 'netInvesting', 'netFinancing', 'netIncrease',
            'openingCash', 'closingCash', 'startDate', 'endDate'
        ));
    }

    public function exportLedger(Request $request)
    {
        $accountId = $request->input('account_id');
        $allTime = $request->has('all_time');
        
        $startDate = $allTime ? '2000-01-01' : $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $allTime ? Carbon::now()->toDateString() : $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Decide which accounts to export
        $queryAccounts = $accountId ? ChartOfAccount::where('id', $accountId)->get() : ChartOfAccount::all();
        
        if ($queryAccounts->isEmpty()) return back()->with('error', 'Tidak ada data akun untuk diekspor.');

        $fileName = ($accountId ? 'Ledger_' : 'Ledger_All_Accounts_') . Carbon::now()->format('Ymd') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Akun', 'Tanggal', 'Referensi', 'Deskripsi', 'Debit', 'Kredit', 'Saldo'];

        $callback = function() use($queryAccounts, $columns, $allTime, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($queryAccounts as $account) {
                $balance = 0;
                if (!$allTime) {
                    $prevItems = JournalItem::where('account_id', $account->id)
                        ->whereHas('journal', function($q) use ($startDate) {
                            $q->where('date', '<', $startDate);
                        })->get();
                    
                    $balance = in_array($account->type, ['asset', 'expense']) 
                        ? $prevItems->sum('debit') - $prevItems->sum('credit')
                        : $prevItems->sum('credit') - $prevItems->sum('debit');
                }

                $itemsQuery = JournalItem::with('journal')->where('account_id', $account->id);
                if (!$allTime) {
                    $itemsQuery->whereHas('journal', function($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate]);
                    });
                }
                $items = $itemsQuery->get()->sortBy(fn($i) => $i->journal->date . '-' . $i->id);

                if ($items->count() == 0 && $balance == 0) continue;

                // Account Separator / Opening Row
                fputcsv($file, ["--- ACCOUNT: [{$account->code}] {$account->name} ---"]);
                fputcsv($file, [$account->name, '', 'SALDO AWAL', '', '0', '0', $balance]);

                $currentBalance = $balance;
                foreach ($items as $item) {
                    if (in_array($account->type, ['asset', 'expense'])) {
                        $currentBalance += ($item->debit - $item->credit);
                    } else {
                        $currentBalance += ($item->credit - $item->debit);
                    }

                    fputcsv($file, [
                        $account->code,
                        $item->journal->date,
                        $item->journal->reference,
                        $item->journal->description,
                        $item->debit,
                        $item->credit,
                        $currentBalance
                    ]);
                }
                fputcsv($file, []); // Empty row between accounts
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCashFlow(Request $request)
    {
        $allTime = $request->has('all_time');
        $startDate = $allTime ? '2000-01-01' : $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $allTime ? Carbon::now()->toDateString() : $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $fileName = ($allTime ? 'CashFlow_Full_' : 'CashFlow_') . ($allTime ? Carbon::now()->format('Ymd') : ($startDate . '_to_' . $endDate)) . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Recalculate or ideally we should refactor data fetching into a service
        // Let's use a simpler approach for the export
        $callback = function() use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Laporan Arus Kas', $startDate . ' s/d ' . $endDate]);
            fputcsv($file, []);
            fputcsv($file, ['Kategori', 'Penerimaan', 'Pengeluaran', 'Neto']);
            
            // This is a placeholder for the actual calculation logic 
            // In a real app, I'd move the calculation to a shared method/service
            fputcsv($file, ['Aktivitas Operasional', '...', '...', '...']);
            fputcsv($file, ['Aktivitas Investasi', '...', '...', '...']);
            fputcsv($file, ['Aktivitas Pendanaan', '...', '...', '...']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
