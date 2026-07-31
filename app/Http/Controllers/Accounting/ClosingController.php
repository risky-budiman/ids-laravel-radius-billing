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
            $date = Carbon::now()->startOfMonth()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $period = AccountingPeriod::firstOrCreate(
                ['month' => $month, 'year' => $year],
                ['is_closed' => false]
            );
            $periods[] = $period;
        }

        $periods = new \Illuminate\Database\Eloquent\Collection($periods);
        $periods->load('user');

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

    public function reopen(AccountingPeriod $period)
    {
        // Only Administrator can reopen
        if (!auth()->user()->isAdministrator()) {
            abort(403, 'Hanya Administrator yang diperbolehkan membuka kembali periode.');
        }

        if (!$period->is_closed) {
            return back()->with('error', 'Periode ini memang sedang terbuka.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($period) {
            // 1. Reopen period
            $period->update([
                'is_closed' => false,
                'closed_at' => null,
                'closed_by' => null,
                'net_profit' => 0,
                'total_assets' => 0,
                'total_liabilities' => 0,
                'total_equity' => 0,
            ]);

            // 2. Find the latest still closed period to update setting
            $latestClosed = AccountingPeriod::where('is_closed', true)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();

            if ($latestClosed) {
                $lastDayOfMonth = Carbon::create($latestClosed->year, $latestClosed->month, 1)->endOfMonth()->toDateString();
                Setting::updateOrCreate(
                    ['key' => 'accounting_closed_until'],
                    ['value' => $lastDayOfMonth, 'group' => 'accounting', 'type' => 'date']
                );
            } else {
                Setting::where('key', 'accounting_closed_until')->delete();
            }

            \Illuminate\Support\Facades\Cache::forget('app_settings');
        });

        return back()->with('success', "Periode {$period->period_string} berhasil dibuka kembali.");
    }

    public function syncJournals()
    {
        // Only Administrator can sync
        if (!auth()->user()->isAdministrator()) {
            abort(403, 'Hanya Administrator yang diperbolehkan menyinkronkan jurnal.');
        }

        $lockSetting = Setting::where('key', 'accounting_closed_until')->first();
        $originalLockValue = $lockSetting ? $lockSetting->value : null;

        if ($lockSetting) {
            $lockSetting->delete();
            \Illuminate\Support\Facades\Cache::forget('app_settings');
        }

        $GLOBALS['bypass_accounting_lock'] = true;

        try {
            \Illuminate\Support\Facades\DB::transaction(function() {
                $accountingService = new \App\Services\AccountingService();
                $transactions = \App\Models\BankTransaction::all();
                
                foreach ($transactions as $trx) {
                    // Delete old auto-journal entry
                    \App\Models\Journal::where('reference', 'TRX-' . $trx->id)->delete();
                    
                    // Re-create auto-journal entry
                    $accountingService->recordBankTransaction($trx);
                }
            });
        } finally {
            $GLOBALS['bypass_accounting_lock'] = false;
            // Restore lock date
            if ($originalLockValue) {
                Setting::updateOrCreate(
                    ['key' => 'accounting_closed_until'],
                    ['value' => $originalLockValue, 'group' => 'accounting', 'type' => 'date']
                );
                \Illuminate\Support\Facades\Cache::forget('app_settings');
            }
        }

        return back()->with('success', 'Jurnal transaksi bank berhasil disinkronisasi ulang dengan COA terbaru.');
    }
}
