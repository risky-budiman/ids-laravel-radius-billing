<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ActivityLog;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadPostAuth;
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSubscribers = Customer::count();
        $activeUsers = Customer::where('is_active', true)->count();
        
        $unpaidInvoices = 0;
        $revenue = 0;
        $expense = 0;
        $profit = 0;

        if (class_exists(Invoice::class)) {
            $unpaidInvoices = Invoice::where('status', 'unpaid')->count();
            
            // Calculate Profit & Loss from Journals (as requested)
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();

            // 1. Fetch Income Accounts
            $incomeAccounts = ChartOfAccount::where('type', 'income')
                ->where('is_active', true)
                ->with(['journalItems' => function($q) use ($startDate, $endDate) {
                    $q->whereHas('journal', function($jq) use ($startDate, $endDate) {
                        $jq->whereBetween('date', [$startDate, $endDate]);
                    });
                }])
                ->get();
            
            $revenue = $incomeAccounts->sum(function($account) {
                return $account->journalItems->sum('credit') - $account->journalItems->sum('debit');
            });

            // 2. Fetch Expense Accounts
            $expenseAccounts = ChartOfAccount::where('type', 'expense')
                ->where('is_active', true)
                ->with(['journalItems' => function($q) use ($startDate, $endDate) {
                    $q->whereHas('journal', function($jq) use ($startDate, $endDate) {
                        $jq->whereBetween('date', [$startDate, $endDate]);
                    });
                }])
                ->get();
            
            $expense = $expenseAccounts->sum(function($account) {
                return $account->journalItems->sum('debit') - $account->journalItems->sum('credit');
            });

            $profit = $revenue - $expense;
        }

        $latestActivities = ActivityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        // ── Live Traffic Data from RADIUS ──
        $onlineNow = RadAcct::online()->count();

        $onlineSessions = RadAcct::online()
            ->selectRaw('SUM(acctinputoctets) as total_upload, SUM(acctoutputoctets) as total_download')
            ->first();

        $totalUpload = $onlineSessions->total_upload ?? 0;
        $totalDownload = $onlineSessions->total_download ?? 0;

        // Top 5 users by current session traffic
        $topUsers = RadAcct::online()
            ->selectRaw('username, framedipaddress, acctsessiontime, (acctinputoctets + acctoutputoctets) as total_traffic, acctstarttime')
            ->orderByDesc('total_traffic')
            ->limit(5)
            ->get();

        // Hourly traffic for the last 24 hours (for chart)
        $hourlyTraffic = RadAcct::where('acctstarttime', '>=', now()->subHours(24))
            ->selectRaw('HOUR(acctstarttime) as hour, COUNT(*) as sessions, SUM(acctinputoctets) as upload, SUM(acctoutputoctets) as download')
            ->groupByRaw('HOUR(acctstarttime)')
            ->orderByRaw('HOUR(acctstarttime)')
            ->get()
            ->keyBy('hour');

        // Build 24-hour data array
        $chartLabels = [];
        $chartUpload = [];
        $chartDownload = [];
        $chartSessions = [];
        for ($i = 23; $i >= 0; $i--) {
            $h = now()->subHours($i)->format('H');
            $hourInt = (int) $h;
            $chartLabels[] = $h . ':00';
            $chartUpload[] = round(($hourlyTraffic[$hourInt]->upload ?? 0) / 1048576, 2);
            $chartDownload[] = round(($hourlyTraffic[$hourInt]->download ?? 0) / 1048576, 2);
            $chartSessions[] = $hourlyTraffic[$hourInt]->sessions ?? 0;
        }

        // Auth stats from radpostauth
        $authAcceptToday = RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Accept')->count();
        $authRejectToday = RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Reject')->count();

        // PSB Stats (New Installations based on activated_at)
        $psbToday = Customer::whereDate('activated_at', today())->count();
        $psbMonth = Customer::whereMonth('activated_at', now()->month)
            ->whereYear('activated_at', now()->year)
            ->count();
        $psbYear = Customer::whereYear('activated_at', now()->year)->count();

        return view('dashboard', compact(
            'totalSubscribers', 'activeUsers', 'unpaidInvoices', 'revenue', 'expense', 'profit', 'latestActivities',
            'onlineNow', 'totalUpload', 'totalDownload', 'topUsers',
            'chartLabels', 'chartUpload', 'chartDownload', 'chartSessions',
            'authAcceptToday', 'authRejectToday',
            'psbToday', 'psbMonth', 'psbYear'
        ));
    }
}
