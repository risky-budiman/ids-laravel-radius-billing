<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Piutang pelanggan (umur tagihan) per tanggal, dan daftar pelanggan yang akan diisolir
 * pada run billing berikutnya (00:01).
 *
 * Piutang = invoice berstatus unpaid, kecuali pembelian booster (INV-BOOST-*) yang belum
 * dibayar: itu checkout yang ditinggalkan, bukan tagihan.
 */
class ReceivablesService
{
    /** Kelompok umur: kunci => [label, hari terlambat minimal, maksimal] */
    public const BUCKETS = [
        'current' => ['Belum jatuh tempo', null, 0],
        'd30' => ['1–30 hari', 1, 30],
        'd60' => ['31–60 hari', 31, 60],
        'd90' => ['61–90 hari', 61, 90],
        'd90plus' => ['> 90 hari', 91, null],
    ];

    public function build(?CarbonImmutable $asOf = null): array
    {
        $asOf = ($asOf ?? CarbonImmutable::today())->startOfDay();

        $invoices = Invoice::with('customer:id,name,customer_code,username,phone,status,is_active')
            ->where('status', 'unpaid')
            ->where('invoice_number', 'not like', 'INV-BOOST-%')
            ->get(['id', 'customer_id', 'invoice_number', 'amount', 'due_date', 'period_start', 'period_end']);

        $rows = $invoices->map(function (Invoice $i) use ($asOf) {
            $days = $i->due_date ? (int) $i->due_date->startOfDay()->diffInDays($asOf, false) : 0;

            return ['invoice' => $i, 'days' => $days, 'bucket' => self::bucketFor($days), 'amount' => (float) $i->amount];
        });

        $summary = [];
        foreach (self::BUCKETS as $key => [$label]) {
            $in = $rows->where('bucket', $key);
            $summary[$key] = ['label' => $label, 'count' => $in->count(), 'amount' => round($in->sum('amount'), 2)];
        }

        $customers = $rows->groupBy(fn ($r) => $r['invoice']->customer_id)->map(function (Collection $items) {
            $customer = $items->first()['invoice']->customer;
            $buckets = array_fill_keys(array_keys(self::BUCKETS), 0.0);
            foreach ($items as $r) {
                $buckets[$r['bucket']] += $r['amount'];
            }

            return [
                'customer' => $customer,
                'invoice_count' => $items->count(),
                'total' => round($items->sum('amount'), 2),
                'oldest_due' => $items->min(fn ($r) => $r['invoice']->due_date),
                'max_days' => (int) $items->max('days'),
                'buckets' => $buckets,
                'invoices' => $items->sortBy(fn ($r) => $r['invoice']->due_date)->values(),
            ];
        })->sortByDesc(fn ($c) => [$c['max_days'], $c['total']])->values();

        $overdue = $rows->where('days', '>', 0);

        return [
            'asOf' => $asOf,
            'summary' => $summary,
            'customers' => $customers,
            'total' => round($rows->sum('amount'), 2),
            'totalCount' => $rows->count(),
            'overdueAmount' => round($overdue->sum('amount'), 2),
            'over30Amount' => round($rows->where('days', '>', 30)->sum('amount'), 2),
            'customerCount' => $customers->count(),
            'toBeSuspended' => $this->toBeSuspended($asOf),
        ];
    }

    /**
     * Pelanggan aktif yang akan diisolir pada run billing berikutnya: punya tagihan langganan
     * yang jatuh tempo paling lambat hari ini (diisolir bila jatuh tempo < tanggal run),
     * atau masa aktif renewal yang habis hari ini.
     */
    public function toBeSuspended(CarbonImmutable $asOf): Collection
    {
        $nextRun = $asOf->addDay();

        return Customer::where('is_active', true)
            ->where('status', Customer::STATUS_ACTIVE)
            ->where(function ($q) use ($nextRun) {
                $q->whereHas('invoices', fn ($i) => $i->overdueRecurring($nextRun))
                  ->orWhere(fn ($r) => $r->where('billing_type', 'prepaid')->where('billing_method', 'renewal')
                      ->whereNotNull('expired_at')->whereDate('expired_at', '<', $nextRun->toDateString()));
            })
            ->with(['invoices' => fn ($i) => $i->overdueRecurring($nextRun)->orderBy('due_date')])
            ->orderBy('name')
            ->get()
            ->map(fn (Customer $c) => [
                'customer' => $c,
                'invoices' => $c->invoices,
                'total' => round((float) $c->invoices->sum('amount'), 2),
                'reason' => $c->invoices->isNotEmpty() ? 'Tagihan jatuh tempo' : 'Masa aktif habis',
            ]);
    }

    public static function bucketFor(int $daysOverdue): string
    {
        foreach (self::BUCKETS as $key => [, $min, $max]) {
            if (($min === null || $daysOverdue >= $min) && ($max === null || $daysOverdue <= $max)) {
                return $key;
            }
        }

        return 'd90plus';
    }
}
