<?php

namespace App\Http\Controllers;

use App\Services\Reports\TicketRecapService;
use App\Models\Ticket;
use App\Support\Period;
use App\Support\SpreadsheetExport;
use Illuminate\Http\Request;

/**
 * Rekap tiket bulanan / tahunan untuk audit pekerjaan tim (administrator & admin).
 */
class TicketRecapController extends Controller
{
    public function __construct(private TicketRecapService $recap)
    {
    }

    public function index(Request $request)
    {
        [$year, $month] = Period::fromRequest($request);
        $data = $this->recap->build($year, $month);

        return view('tickets.recap', $data + [
            'years' => Period::years(Ticket::min('created_at')),
            'periodLabel' => Period::label($year, $month),
        ]);
    }

    public function export(Request $request)
    {
        [$year, $month] = Period::fromRequest($request);
        $d = $this->recap->build($year, $month);
        $label = Period::label($year, $month);
        $fmt = TicketRecapService::class;
        $sla = $d['slaHours'];

        $xlsx = new SpreadsheetExport((string) get_setting('company_name', config('app.name')));

        // 1. Kinerja per petugas
        $typeKeys = array_keys(TicketRecapService::TYPES);
        $xlsx->sheet('Kinerja Petugas', ["Rekap Kinerja Tim — $label", "SLA penyelesaian: ≤ $sla jam sejak tiket dibuat"],
            array_merge(['Petugas', 'Ditugaskan (masuk)', 'Selesai'], array_map(fn ($t) => 'Selesai ' . TicketRecapService::TYPES[$t], $typeKeys),
                ['Dibatalkan', 'Masih terbuka', 'Rata-rata durasi', 'Sesuai SLA', 'Lewat SLA', '% SLA']),
            array_map(fn ($r) => array_merge([$r['name'], $r['masuk'], $r['selesai']], array_values($r['selesai_per_tipe']),
                [$r['dibatalkan'], $r['terbuka'], $fmt::formatDuration($r['avg_minutes']), $r['sla_ok'], $r['sla_late'], $r['sla_pct']]), $d['byStaff']),
            [1 => 'int', 2 => 'int', 3 => 'int', 4 => 'int', 5 => 'int', 6 => 'int', 7 => 'int', 9 => 'int', 10 => 'int', 11 => 'pct'],
            array_merge(['Total', $d['summary']['masuk'], $d['summary']['selesai']], array_map(fn ($t) => $d['byType'][$t]['selesai'], $typeKeys),
                [$d['summary']['dibatalkan'], $d['summary']['terbuka'], $fmt::formatDuration($d['summary']['avg_minutes']), $d['summary']['sla_ok'], $d['summary']['sla_late'], $d['summary']['sla_pct']]),
        );

        // 2. Per tipe tiket
        $xlsx->sheet('Per Tipe', ["Rekap per Tipe Tiket — $label"],
            ['Tipe', 'Masuk', 'Selesai', 'Dibatalkan', 'Masih terbuka', 'Rata-rata durasi', 'Sesuai SLA', 'Lewat SLA', '% SLA'],
            array_map(fn ($r) => [$r['label'], $r['masuk'], $r['selesai'], $r['dibatalkan'], $r['terbuka'], $fmt::formatDuration($r['avg_minutes']), $r['sla_ok'], $r['sla_late'], $r['sla_pct']], array_values($d['byType'])),
            [1 => 'int', 2 => 'int', 3 => 'int', 4 => 'int', 6 => 'int', 7 => 'int', 8 => 'pct'],
            ['Total', $d['summary']['masuk'], $d['summary']['selesai'], $d['summary']['dibatalkan'], $d['summary']['terbuka'], $fmt::formatDuration($d['summary']['avg_minutes']), $d['summary']['sla_ok'], $d['summary']['sla_late'], $d['summary']['sla_pct']],
        );

        if ($month) {
            // 3. Rincian tiket (audit)
            $xlsx->sheet('Rincian Tiket', ["Rincian Tiket — $label", 'Tiket yang masuk atau diselesaikan dalam periode'],
                ['No. Tiket', 'Dibuat', 'Tipe', 'Prioritas', 'Pelanggan', 'ID Pelanggan', 'Subjek', 'Petugas', 'Status', 'Selesai', 'Durasi', 'SLA'],
                $d['tickets']->map(fn ($t) => [
                    $t['number'], $t['created_at'], TicketRecapService::TYPES[$t['type']] ?? $t['type'], ucfirst((string) $t['priority']),
                    $t['customer'], $t['customer_code'], $t['subject'], $t['staff'] ?? 'Belum ditugaskan', $this->statusLabel($t['status']),
                    $t['resolved_at'], $t['minutes'] === null ? '' : $fmt::formatDuration($t['minutes']),
                    $t['sla_ok'] === null ? '' : ($t['sla_ok'] ? 'Sesuai' : 'Lewat'),
                ])->all(),
                [1 => 'datetime', 9 => 'datetime'],
                null,
                [20, 17, 11, 10, 26, 14, 40, 20, 12, 17, 13, 8],
            );
        } else {
            // 3. Per bulan & 4. Matriks petugas x bulan
            $months = Period::MONTHS;
            $xlsx->sheet('Per Bulan', ["Rekap Tiket per Bulan — $label"],
                ['Bulan', 'Masuk', 'Masuk Aktivasi', 'Masuk Gangguan', 'Masuk Dismantle', 'Selesai', 'Dibatalkan', 'Masih terbuka', 'Rata-rata durasi', '% SLA'],
                array_map(fn ($m, $r) => [$months[$m], $r['masuk'], $r['masuk_aktivasi'], $r['masuk_gangguan'], $r['masuk_dismantle'], $r['selesai'], $r['dibatalkan'], $r['terbuka'], $fmt::formatDuration($r['avg_minutes']), $r['sla_pct']],
                    array_keys($d['byMonth']), $d['byMonth']),
                [1 => 'int', 2 => 'int', 3 => 'int', 4 => 'int', 5 => 'int', 6 => 'int', 7 => 'int', 9 => 'pct'],
                ['Total', $d['summary']['masuk'], $d['byType']['aktivasi']['masuk'], $d['byType']['gangguan']['masuk'], $d['byType']['dismantle']['masuk'], $d['summary']['selesai'], $d['summary']['dibatalkan'], $d['summary']['terbuka'], $fmt::formatDuration($d['summary']['avg_minutes']), $d['summary']['sla_pct']],
            );

            $short = array_map(fn ($n) => mb_substr($n, 0, 3), $months);
            $colTotals = array_fill(1, 12, 0);
            foreach ($d['staffByMonth'] as $row) {
                foreach ($row['months'] as $m => $n) {
                    $colTotals[$m] += $n;
                }
            }
            $xlsx->sheet('Petugas x Bulan', ["Tiket Diselesaikan per Petugas per Bulan — $label"],
                array_merge(['Petugas'], array_values($short), ['Total']),
                array_map(fn ($r) => array_merge([$r['name']], array_values($r['months']), [$r['total']]), $d['staffByMonth']),
                array_fill_keys(range(1, 13), 'int'),
                array_merge(['Total'], array_values($colTotals), [array_sum($colTotals)]),
            );
        }

        $file = 'Rekap-Tiket-' . ($month ? sprintf('%d-%02d', $year, $month) : $year) . '.xlsx';

        return $xlsx->download($file);
    }

    private function statusLabel(string $status): string
    {
        return ['open' => 'Open', 'in_progress' => 'Dikerjakan', 'resolved' => 'Resolved', 'closed' => 'Closed', 'canceled' => 'Dibatalkan'][$status] ?? $status;
    }
}
