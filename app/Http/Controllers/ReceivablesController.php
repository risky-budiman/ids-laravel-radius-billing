<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReceivablesService;
use App\Support\SpreadsheetExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Laporan piutang (umur tagihan) & daftar pelanggan yang akan diisolir — administrator, admin, kasir.
 */
class ReceivablesController extends Controller
{
    public function __construct(private ReceivablesService $receivables)
    {
    }

    public function index(Request $request)
    {
        return view('receivables.index', $this->receivables->build($this->asOf($request)));
    }

    public function export(Request $request)
    {
        $d = $this->receivables->build($this->asOf($request));
        $date = $d['asOf']->format('d/m/Y');
        $keys = array_keys(ReceivablesService::BUCKETS);
        $labels = array_column(ReceivablesService::BUCKETS, 0);

        $xlsx = new SpreadsheetExport((string) get_setting('company_name', config('app.name')));

        $xlsx->sheet('Per Pelanggan', ["Piutang Pelanggan per $date", 'Umur dihitung dari tanggal jatuh tempo invoice'],
            array_merge(['Pelanggan', 'ID Pelanggan', 'Status', 'No. HP', 'Invoice', 'Jatuh tempo tertua', 'Terlambat (hari)'], $labels, ['Total']),
            $d['customers']->map(fn ($c) => array_merge([
                $c['customer']?->name, $c['customer']?->customer_code, $this->statusLabel($c['customer']?->status), $c['customer']?->phone,
                $c['invoice_count'], $c['oldest_due'], max(0, $c['max_days']),
            ], array_map(fn ($k) => $c['buckets'][$k] ?: null, $keys), [$c['total']]))->all(),
            array_merge([4 => 'int', 5 => 'date', 6 => 'int'], array_fill_keys(range(7, 7 + count($keys)), 'money')),
            array_merge(['Total', null, null, null, $d['totalCount'], null, null], array_map(fn ($k) => $d['summary'][$k]['amount'], $keys), [$d['total']]),
        );

        $xlsx->sheet('Rincian Invoice', ["Rincian Invoice Belum Dibayar per $date"],
            ['No. Invoice', 'Pelanggan', 'ID Pelanggan', 'Periode', 'Jatuh tempo', 'Terlambat (hari)', 'Umur', 'Jumlah'],
            $d['customers']->flatMap(fn ($c) => $c['invoices']->map(fn ($r) => [
                $r['invoice']->invoice_number, $c['customer']?->name, $c['customer']?->customer_code,
                $r['invoice']->period_start ? $r['invoice']->period_start->format('d/m/Y') . ' – ' . $r['invoice']->period_end?->format('d/m/Y') : null,
                $r['invoice']->due_date, max(0, $r['days']), ReceivablesService::BUCKETS[$r['bucket']][0], $r['amount'],
            ]))->all(),
            [4 => 'date', 5 => 'int', 7 => 'money'],
            ['Total', null, null, null, null, null, null, $d['total']],
        );

        $xlsx->sheet('Akan Diisolir', ['Akan Diisolir pada Run Billing Berikutnya', 'Run berikutnya: ' . $d['asOf']->addDay()->format('d/m/Y') . ' 00:01'],
            ['Pelanggan', 'ID Pelanggan', 'No. HP', 'Alasan', 'Invoice', 'Total'],
            $d['toBeSuspended']->map(fn ($s) => [
                $s['customer']->name, $s['customer']->customer_code, $s['customer']->phone, $s['reason'],
                $s['invoices']->pluck('invoice_number')->implode(', '), $s['total'],
            ])->all(),
            [5 => 'money'],
            ['Total', null, null, null, null, $d['toBeSuspended']->sum('total')],
        );

        return $xlsx->download('Piutang-' . $d['asOf']->format('Y-m-d') . '.xlsx');
    }

    private function asOf(Request $request): CarbonImmutable
    {
        $request->validate(['date' => 'nullable|date']);

        return $request->filled('date') ? CarbonImmutable::parse($request->input('date')) : CarbonImmutable::today();
    }

    private function statusLabel(?string $status): string
    {
        return ['new' => 'Baru', 'waiting_activation' => 'Menunggu aktivasi', 'active' => 'Aktif', 'suspended' => 'Terisolir',
            'waiting_dismantle' => 'Menunggu dismantle', 'dismantled' => 'Dicabut', 'canceled' => 'Batal'][$status ?? ''] ?? (string) $status;
    }
}
