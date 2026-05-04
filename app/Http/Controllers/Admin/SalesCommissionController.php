<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesCommission;
use App\Models\SalesWithdrawal;
use App\Models\User;
use App\Services\SalesCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesCommissionController extends Controller
{
    protected $salesService;

    public function __construct(SalesCommissionService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index()
    {
        $salesStaff = User::where('role', User::ROLE_SALES)->orWhere('is_sales', true)->get();
        
        foreach ($salesStaff as $staff) {
            $staff->balance = $this->salesService->getSalesBalance($staff->id);
            $staff->total_earned = SalesCommission::where('sales_id', $staff->id)->sum('commission_amount');
            $staff->total_withdrawn = SalesWithdrawal::where('sales_id', $staff->id)->where('status', 'paid')->sum('amount');
        }

        $recentCommissions = SalesCommission::with(['sales', 'customer', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.sales.index', compact('salesStaff', 'recentCommissions'));
    }

    public function show(User $sales)
    {
        if (!$sales->isSales()) abort(404);

        $commissions = SalesCommission::with(['customer', 'invoice'])
            ->where('sales_id', $sales->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'commissions_page');

        $withdrawals = SalesWithdrawal::where('sales_id', $sales->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'withdrawals_page');

        $customers = \App\Models\Customer::with(['package'])
            ->where('sales_id', $sales->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'customers_page');

        $balance = $this->salesService->getSalesBalance($sales->id);

        return view('admin.sales.show', compact('sales', 'commissions', 'withdrawals', 'customers', 'balance'));
    }

    public function storeWithdrawal(Request $request, User $sales)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
        ]);

        $balance = $this->salesService->getSalesBalance($sales->id);

        if ($validated['amount'] > $balance) {
            return redirect()->back()->withErrors(['amount' => 'Insufficient balance. Max: Rp ' . number_format($balance, 0)]);
        }

        try {
            $this->salesService->recordWithdrawal(
                $sales->id,
                $validated['amount'],
                $validated['bank_account_id'],
                $validated['reference_number'],
                $validated['notes']
            );

            return redirect()->back()->with('success', 'Withdrawal recorded and journaled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to record withdrawal: ' . $e->getMessage());
        }
    }
}
