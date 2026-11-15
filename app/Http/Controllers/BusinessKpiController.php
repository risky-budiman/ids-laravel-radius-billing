<?php

namespace App\Http\Controllers;

use App\Services\Reports\BusinessKpiService;
use App\Support\SpreadsheetExport;
use Illuminate\Http\Request;

/**
 * Dashboard KPI bisnis (administrator & admin): MRR, ARPU, churn, pelanggan baru vs berhenti.
 */
class BusinessKpiController extends Controller
{
    public function __construct(private BusinessKpiService $kpi)
    {
    }

    public function index(Request $request)
    {
        return view('kpi.index', $this->kpi->build($this->months($request)));
    }

    public function export(Request $request)
    {
        $d = $this->kpi->build($this->months($request));

        return (new SpreadsheetExport((string) get_setting('company_name', config('app.name'))))
            ->sheet('KPI Bulanan', ['KPI Bisnis — ' . $d['months'] . ' bulan terakhir',
                'MRR & ARPU tanpa PPN, harga paket saat ini · churn = berhenti / berlangganan di awal bulan'],
                ['Bulan', 'Pelanggan awal', 'Baru', 'Berhenti', 'Pertumbuhan bersih', 'Pelanggan akhir', 'Churn (%)', 'MRR', 'ARPU', 'Ditagih', 'Diterima'],
                array_map(fn ($r) => [
                    $r['month']->locale('id')->translatedFormat('F Y') . ($r['is_current'] ? ' (berjalan)' : ''),
                    $r['start_count'], $r['new'], $r['stopped'], $r['net'], $r['end_count'], $r['churn_pct'],
                    $r['mrr'], $r['arpu'], $r['billed'], $r['collected'],
                ], array_values($d['rows'])),
                [1 => 'int', 2 => 'int', 3 => 'int', 4 => 'int', 5 => 'int', 6 => 'pct', 7 => 'money', 8 => 'money', 9 => 'money', 10 => 'money'],
                ['Total', null, $d['totalNew'], $d['totalStopped'], $d['totalNew'] - $d['totalStopped'], null, $d['avgChurn'], null, null,
                    array_sum(array_column($d['rows'], 'billed')), array_sum(array_column($d['rows'], 'collected'))],
            )
            ->download('KPI-Bisnis-' . $d['asOf']->format('Y-m-d') . '.xlsx');
    }

    private function months(Request $request): int
    {
        $request->validate(['months' => 'nullable|integer|in:6,12,24']);

        return (int) $request->input('months', 12);
    }
}
