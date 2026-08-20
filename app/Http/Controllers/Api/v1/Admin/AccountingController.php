<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Http\Request;

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
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Profit & Loss report summary.
     */
    public function profitLoss()
    {
        $revenueAccounts = ChartOfAccount::where('type', 'revenue')->get();
        $expenseAccounts = ChartOfAccount::where('type', 'expense')->get();

        $totalRevenue = $revenueAccounts->sum('balance');
        $totalExpense = $expenseAccounts->sum('balance');
        $netProfit = $totalRevenue - $totalExpense;

        return response()->json([
            'total_revenue' => (float) $totalRevenue,
            'total_expense' => (float) $totalExpense,
            'net_profit' => (float) $netProfit,
            'is_profit' => $netProfit >= 0,
            'revenue_breakdown' => $revenueAccounts->map(fn ($a) => [
                'name' => $a->name,
                'code' => $a->code,
                'amount' => (float) $a->balance,
            ]),
            'expense_breakdown' => $expenseAccounts->map(fn ($a) => [
                'name' => $a->name,
                'code' => $a->code,
                'amount' => (float) $a->balance,
            ]),
        ]);
    }

    /**
     * Balance Sheet report summary.
     */
    public function balanceSheet()
    {
        $assets = ChartOfAccount::where('type', 'asset')->whereNull('parent_id')->get()->sum('balance');
        $liabilities = ChartOfAccount::where('type', 'liability')->whereNull('parent_id')->get()->sum('balance');
        $equity = ChartOfAccount::where('type', 'equity')->whereNull('parent_id')->get()->sum('balance');

        return response()->json([
            'total_assets' => (float) $assets,
            'total_liabilities' => (float) $liabilities,
            'total_equity' => (float) $equity,
            'is_balanced' => abs($assets - ($liabilities + $equity)) < 0.01,
        ]);
    }
}
