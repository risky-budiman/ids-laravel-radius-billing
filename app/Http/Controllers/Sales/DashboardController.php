<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesCommission;
use App\Models\SalesWithdrawal;
use App\Services\SalesCommissionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $salesService;

    public function __construct(SalesCommissionService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index()
    {
        $user = auth()->user();
        
        $balance = $this->salesService->getSalesBalance($user->id);
        $totalEarned = SalesCommission::where('sales_id', $user->id)->sum('commission_amount');
        $totalWithdrawn = SalesWithdrawal::where('sales_id', $user->id)->where('status', 'paid')->sum('amount');
        
        $referrals = Customer::where('sales_id', $user->id)->count();

        $recentCommissions = SalesCommission::with(['customer', 'invoice'])
            ->where('sales_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentWithdrawals = SalesWithdrawal::where('sales_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('sales.dashboard', compact(
            'balance', 'totalEarned', 'totalWithdrawn', 'referrals',
            'recentCommissions', 'recentWithdrawals'
        ));
    }

    public function ledger()
    {
        $user = auth()->user();
        
        $commissions = SalesCommission::with(['customer', 'invoice'])
            ->where('sales_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sales.ledger', compact('commissions'));
    }

    public function customers()
    {
        $user = auth()->user();
        
        $customers = Customer::where('sales_id', $user->id)
            ->with(['package'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sales.customers', compact('customers'));
    }
}
