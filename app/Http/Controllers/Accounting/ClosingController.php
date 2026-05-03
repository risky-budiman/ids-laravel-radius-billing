<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\AccountingPeriod;
use App\Services\AccountingService;
use Carbon\Carbon;

class ClosingController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index()
    {
        // Get or generate periods for the last 12 months
        $periods = [];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $period = AccountingPeriod::firstOrCreate(
                ['month' => $month, 'year' => $year],
                ['is_closed' => false]
            );
            $periods[] = $period;
        }

        $closedUntil = get_setting('accounting_closed_until');
        return view('accounting.closing.index', compact('periods', 'closedUntil'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:accounting_periods,id',
            'confirmation' => 'required|accepted',
        ]);

        $period = AccountingPeriod::findOrFail($request->period_id);

        if ($period->is_closed) {
            return back()->with('error', 'Periode ini sudah ditutup.');
        }

        // 1. Calculate Snapshot
        $snapshot = $this->accountingService->getFinancialSnapshot($period->month, $period->year);

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

        // 3. Update global lock setting (Closed until last day of this month)
        $lastDayOfMonth = Carbon::create($period->year, $period->month, 1)->endOfMonth()->toDateString();
        
        $currentLock = get_setting('accounting_closed_until');
        if (!$currentLock || Carbon::parse($lastDayOfMonth)->gt(Carbon::parse($currentLock))) {
            $setting = Setting::updateOrCreate(
                ['key' => 'accounting_closed_until'],
                ['value' => $lastDayOfMonth, 'group' => 'accounting', 'type' => 'date']
            );
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        }

        return back()->with('success', "Periode {$period->period_string} berhasil ditutup. Laporan snapshot telah disimpan.");
    }
}
