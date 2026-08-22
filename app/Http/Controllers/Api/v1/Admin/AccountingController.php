<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Setting;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    /**
     * Get Chart of Accounts.
     */
    public function coa()
    {
        $accounts = ChartOfAccount::orderBy('code', 'asc')->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'balance' => (float) $a->balance,
                'parent_id' => $a->parent_id,
            ];
        });

        return response()->json([
            'accounts' => $accounts,
        ]);
    }

    /**
     * List journal entries.
     */
    public function journals(Request $request)
    {
        $query = Journal::with(['items.account', 'creator'])->latest('date');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where('description', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%");
        }

        $paginator = $query->paginate(15);

        $data = $paginator->getCollection()->map(function ($j) {
            $items = $j->items->map(function ($i) {
                return [
                    'id' => $i->id,
                    'account_code' => $i->account ? $i->account->code : '-',
                    'account_name' => $i->account ? $i->account->name : 'N/A',
                    'debit' => (float) $i->debit,
                    'credit' => (float) $i->credit,
                    'memo' => $i->memo,
                ];
            });

            return [
                'id' => $j->id,
                'date' => $j->date ? $j->date->format('Y-m-d') : null,
                'reference' => $j->reference,
                'description' => $j->description,
                'creator_name' => $j->creator ? $j->creator->name : 'System',
                'total_debit' => (float) $j->items->sum('debit'),
                'total_credit' => (float) $j->items->sum('credit'),
                'items' => $items,
            ];
        });

        return response()->json([
            'journals' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Profit & Loss report summary.
     */
    public function profitLoss(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Income Accounts
        $incomeAccounts = ChartOfAccount::whereIn('type', ['income', 'revenue'])
            ->where('is_active', true)
            ->with(['journalItems' => function($q) use ($startDate, $endDate) {
                $q->whereHas('journal', function($jq) use ($startDate, $endDate) {
                    $jq->whereBetween('date', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->map(function($account) {
                $account->period_balance = (float)($account->journalItems->sum('credit') - $account->journalItems->sum('debit'));
                return $account;
            });

        // Expense Accounts
        $expenseAccounts = ChartOfAccount::whereIn('type', ['expense', 'cost_of_sales'])
            ->where('is_active', true)
            ->with(['journalItems' => function($q) use ($startDate, $endDate) {
                $q->whereHas('journal', function($jq) use ($startDate, $endDate) {
                    $jq->whereBetween('date', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->map(function($account) {
                $account->period_balance = (float)($account->journalItems->sum('debit') - $account->journalItems->sum('credit'));
                return $account;
            });

        $totalIncome = (float) $incomeAccounts->sum('period_balance');
        $totalExpense = (float) $expenseAccounts->sum('period_balance');
        $netProfit = $totalIncome - $totalExpense;

        return response()->json([
            'total_revenue' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
            'is_profit' => $netProfit >= 0,
            'revenue_breakdown' => $incomeAccounts->filter(fn($a) => $a->period_balance != 0)->values()->map(fn ($a) => [
                'name' => $a->name,
                'code' => $a->code,
                'amount' => (float) $a->period_balance,
            ]),
            'expense_breakdown' => $expenseAccounts->filter(fn($a) => $a->period_balance != 0)->values()->map(fn ($a) => [
                'name' => $a->name,
                'code' => $a->code,
                'amount' => (float) $a->period_balance,
            ]),
        ]);
    }

    /**
     * Balance Sheet report summary.
     */
    public function balanceSheet(Request $request)
    {
        $date = $request->input('date', Carbon::now()->toDateString());

        // Fetch all accounts with cumulative balance up to $date from journals
        $accounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($date) {
                $items = JournalItem::whereHas('journal', function($q) use ($date) {
                    $q->where('date', '<=', $date);
                })->where('account_id', $account->id)->get();

                if ($account->type === 'asset') {
                    $account->current_balance = (float)($items->sum('debit') - $items->sum('credit'));
                } else {
                    $account->current_balance = (float)($items->sum('credit') - $items->sum('debit'));
                }
                return $account;
            });

        // Add Current Earnings (Net Profit from beginning until $date)
        $incomeItems = JournalItem::whereHas('journal', function($q) use ($date) {
            $q->where('date', '<=', $date);
        })->whereHas('account', fn($q) => $q->whereIn('type', ['income', 'revenue']))->get();

        $expenseItems = JournalItem::whereHas('journal', function($q) use ($date) {
            $q->where('date', '<=', $date);
        })->whereHas('account', fn($q) => $q->whereIn('type', ['expense', 'cost_of_sales']))->get();

        $currentEarnings = (float)(
            ($incomeItems->sum('credit') - $incomeItems->sum('debit')) -
            ($expenseItems->sum('debit') - $expenseItems->sum('credit'))
        );

        $assets = $accounts->where('type', 'asset')->filter(fn($a) => $a->current_balance != 0)->values();
        $liabilities = $accounts->where('type', 'liability')->filter(fn($a) => $a->current_balance != 0)->values();
        $equity = $accounts->where('type', 'equity')->filter(fn($a) => $a->current_balance != 0)->values();

        $totalAssets = (float) $assets->sum('current_balance');
        $totalLiabilities = (float) $liabilities->sum('current_balance');
        $totalEquity = (float) ($equity->sum('current_balance') + $currentEarnings);

        $isBalanced = abs($totalAssets - ($totalLiabilities + $totalEquity)) < 1.0;

        return response()->json([
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'current_earnings' => $currentEarnings,
            'is_balanced' => $isBalanced,
            'assets_breakdown' => $assets->map(fn($a) => ['name' => $a->name, 'code' => $a->code, 'amount' => (float)$a->current_balance]),
            'liabilities_breakdown' => $liabilities->map(fn($a) => ['name' => $a->name, 'code' => $a->code, 'amount' => (float)$a->current_balance]),
            'equity_breakdown' => $equity->map(fn($a) => ['name' => $a->name, 'code' => $a->code, 'amount' => (float)$a->current_balance]),
        ]);
    }

    /**
     * Cash Flow report summary.
     */
    public function cashFlow(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Get all journal items related to Cash/Bank accounts (Code 1001 and its children like 1101, 1102)
        $cashAccounts = ChartOfAccount::where('code', '1001')
            ->orWhere('parent_id', function($query) {
                $query->select('id')->from('chart_of_accounts')->where('code', '1001');
            })
            ->pluck('id');

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

            $offsetItems = $cashItem->journal->items->where('account_id', '!=', $cashItem->account_id);

            foreach ($offsetItems as $offset) {
                if (!$offset->account) continue;
                $type = $offset->account->type;
                $code = $offset->account->code;

                if ($isDebit) {
                    if (in_array($type, ['income', 'revenue']) || $code === '1103') {
                        $operatingIn += $amount;
                    } elseif ($type === 'equity' || $type === 'liability') {
                        $financingIn += $amount;
                    }
                } else {
                    if (in_array($type, ['expense', 'cost_of_sales']) || in_array($code, ['2101', '2103'])) {
                        $operatingOut += $amount;
                    } elseif (in_array($code, ['1104', '1105', '1201', '1202'])) {
                        $investingOut += $amount;
                    }
                }
            }
        }

        $netOperating = (float) ($operatingIn - $operatingOut);
        $netInvesting = (float) (-$investingOut);
        $netFinancing = (float) ($financingIn);
        $netCashFlow = (float) ($netOperating + $netInvesting + $netFinancing);

        return response()->json([
            'operating_in' => (float) $operatingIn,
            'operating_out' => (float) $operatingOut,
            'net_operating' => $netOperating,
            'investing_in' => 0.0,
            'investing_out' => (float) $investingOut,
            'net_investing' => $netInvesting,
            'financing_in' => (float) $financingIn,
            'financing_out' => 0.0,
            'net_financing' => $netFinancing,
            'net_cash_flow' => $netCashFlow,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Tax Summary (PPN) report.
     */
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
            $totalTaxOutput = (float) ($taxOutputItems->sum('credit') - $taxOutputItems->sum('debit'));
        }

        // 2. Tax Input (PPN Masukan - Code 1106)
        $taxInputAccount = ChartOfAccount::where('code', '1106')->first() ?? ChartOfAccount::where('name', 'like', '%PPN Masukan%')->first();
        $totalTaxInput = 0;
        if ($taxInputAccount) {
            $taxInputItems = JournalItem::where('account_id', $taxInputAccount->id)
                ->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })->get();
            $totalTaxInput = (float) ($taxInputItems->sum('debit') - $taxInputItems->sum('credit'));
        }

        $netTaxPayable = (float) ($totalTaxOutput - $totalTaxInput);

        return response()->json([
            'total_tax_output' => (float) $totalTaxOutput,
            'total_tax_input' => (float) $totalTaxInput,
            'net_tax_payable' => $netTaxPayable,
            'output_items' => [],
            'input_items' => [],
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Ledger (Buku Besar) report.
     */
    public function ledger(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $accountId = $request->input('account_id');
        $allTime = $request->boolean('all_time', true);

        $startDate = $allTime ? '2000-01-01' : $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $allTime ? Carbon::now()->toDateString() : $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $queryAccounts = $accountId ? ChartOfAccount::where('id', $accountId)->get() : $accounts;
        $resultGroups = [];

        foreach ($queryAccounts as $account) {
            $openingBalance = 0;
            if (!$allTime) {
                $prevItems = JournalItem::where('account_id', $account->id)
                    ->whereHas('journal', function($q) use ($startDate) {
                        $q->where('date', '<', $startDate);
                    })->get();

                $openingBalance = in_array($account->type, ['asset', 'expense', 'cost_of_sales'])
                    ? (float) ($prevItems->sum('debit') - $prevItems->sum('credit'))
                    : (float) ($prevItems->sum('credit') - $prevItems->sum('debit'));
            }

            $itemsQuery = JournalItem::with('journal')->where('account_id', $account->id);
            if (!$allTime) {
                $itemsQuery->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            }

            $items = $itemsQuery->get()->sortBy(fn($i) => $i->journal ? ($i->journal->date . '-' . $i->id) : $i->id);

            $debit = (float) $items->sum('debit');
            $credit = (float) $items->sum('credit');
            $endingBalance = $openingBalance;
            if (in_array($account->type, ['asset', 'expense', 'cost_of_sales'])) {
                $endingBalance += ($debit - $credit);
            } else {
                $endingBalance += ($credit - $debit);
            }

            if ($items->count() > 0 || $openingBalance != 0 || ($accountId && $accountId == $account->id)) {
                $lineItems = $items->map(fn($item) => [
                    'date' => $item->journal?->date ? $item->journal->date->format('Y-m-d') : null,
                    'reference' => $item->journal?->reference ?? '-',
                    'description' => $item->memo ?: ($item->journal?->description ?? '-'),
                    'debit' => (float) $item->debit,
                    'credit' => (float) $item->credit,
                    'balance' => 0.0,
                ])->values()->all();

                $resultGroups[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'opening_balance' => (float) $openingBalance,
                    'closing_balance' => (float) $endingBalance,
                    'items' => $lineItems,
                ];
            }
        }

        return response()->json([
            'accounts' => $resultGroups,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Get Accounting Closing Periods (Tutup Buku).
     */
    public function closingPeriods(AccountingService $accountingService)
    {
        // Get or generate periods for the last 12 months
        $periods = [];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->startOfMonth()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $period = AccountingPeriod::firstOrCreate(
                ['month' => $month, 'year' => $year],
                ['is_closed' => false]
            );
            $periods[] = $period;
        }

        $periodsCollection = new \Illuminate\Database\Eloquent\Collection($periods);
        $periodsCollection->load('user');

        $data = $periodsCollection->map(function ($p) use ($accountingService) {
            // If the period is closed and snapshot exists, use it. If not closed or snapshot is 0, compute live snapshot!
            if ($p->is_closed && ($p->total_assets != 0 || $p->total_equity != 0)) {
                $netProfit = (float) ($p->net_profit ?? 0);
                $totalAssets = (float) ($p->total_assets ?? 0);
                $totalLiabilities = (float) ($p->total_liabilities ?? 0);
                $totalEquity = (float) ($p->total_equity ?? 0);
            } else {
                $snapshot = $accountingService->getFinancialSnapshot($p->month, $p->year);
                $netProfit = (float) $snapshot['net_profit'];
                $totalAssets = (float) $snapshot['total_assets'];
                $totalLiabilities = (float) $snapshot['total_liabilities'];
                $totalEquity = (float) $snapshot['total_equity'];
            }

            return [
                'id' => $p->id,
                'month' => $p->month,
                'year' => $p->year,
                'period_string' => $p->period_string,
                'is_closed' => (bool) $p->is_closed,
                'closed_at' => $p->closed_at ? $p->closed_at->format('Y-m-d H:i') : null,
                'closed_by_name' => $p->user ? $p->user->name : null,
                'net_profit' => $netProfit,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
            ];
        });

        $closedUntil = get_setting('accounting_closed_until');

        return response()->json([
            'periods' => $data,
            'closed_until' => $closedUntil,
        ]);
    }

    /**
     * Process Period Closing (Tutup Buku).
     */
    public function processClosing(Request $request, AccountingService $accountingService)
    {
        $request->validate([
            'period_id' => 'required|exists:accounting_periods,id',
        ]);

        $period = AccountingPeriod::findOrFail($request->period_id);

        if ($period->is_closed) {
            return response()->json(['message' => 'Periode ini sudah ditutup sebelumnya.'], 422);
        }

        // 1. Calculate Snapshot
        $snapshot = $accountingService->getFinancialSnapshot($period->month, $period->year);

        // 2. Update Period
        $period->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'net_profit' => $snapshot['net_profit'],
            'total_assets' => $snapshot['total_assets'],
            'total_liabilities' => $snapshot['total_liabilities'],
            'total_equity' => $snapshot['total_equity'],
        ]);

        // 3. Update global lock setting
        $lastDayOfMonth = Carbon::create($period->year, $period->month, 1)->endOfMonth()->toDateString();
        $currentLock = get_setting('accounting_closed_until');
        if (!$currentLock || Carbon::parse($lastDayOfMonth)->gt(Carbon::parse($currentLock))) {
            Setting::updateOrCreate(
                ['key' => 'accounting_closed_until'],
                ['value' => $lastDayOfMonth, 'group' => 'accounting', 'type' => 'date']
            );
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        }

        return response()->json([
            'message' => "Periode {$period->period_string} berhasil ditutup. Snapshot keuangan telah tersimpan.",
            'period' => $period,
        ]);
    }

    /**
     * Reopen Period Closing (Buka Kembali Tutup Buku).
     */
    public function reopenClosing(Request $request)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Hanya Administrator yang diperbolehkan membuka kembali periode.'], 403);
        }

        $request->validate([
            'period_id' => 'required|exists:accounting_periods,id',
        ]);

        $period = AccountingPeriod::findOrFail($request->period_id);

        if (!$period->is_closed) {
            return response()->json(['message' => 'Periode ini memang sedang terbuka.'], 422);
        }

        DB::transaction(function () use ($period) {
            $period->update([
                'is_closed' => false,
                'closed_at' => null,
                'closed_by' => null,
                'net_profit' => null,
                'total_assets' => null,
                'total_liabilities' => null,
                'total_equity' => null,
            ]);

            // Re-evaluate global lock
            $latestClosed = AccountingPeriod::where('is_closed', true)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();

            if ($latestClosed) {
                $lockDate = Carbon::create($latestClosed->year, $latestClosed->month, 1)->endOfMonth()->toDateString();
                Setting::updateOrCreate(
                    ['key' => 'accounting_closed_until'],
                    ['value' => $lockDate, 'group' => 'accounting', 'type' => 'date']
                );
            } else {
                Setting::where('key', 'accounting_closed_until')->delete();
            }

            \Illuminate\Support\Facades\Cache::forget('app_settings');
        });

        return response()->json([
            'message' => "Periode {$period->period_string} berhasil dibuka kembali.",
        ]);
    }
}
