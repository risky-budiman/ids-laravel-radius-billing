<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * KPI bisnis bulanan: MRR, ARPU, churn, pelanggan baru vs berhenti, tagihan & penerimaan.
 *
 * Definisi:
 *  - Pelanggan berlangganan pada suatu saat = sudah aktif (activated_at; data lama tanpa tanggal
 *    aktivasi memakai created_at) dan belum berhenti (terminated_at). Terisolir tetap dihitung
 *    karena tetap ditagih.
 *  - MRR = jumlah harga paket bulanan (setelah diskon pelanggan, tanpa PPN) dari pelanggan yang
 *    berlangganan di akhir bulan. Memakai harga paket SAAT INI (riwayat harga tidak tersimpan).
 *  - ARPU = MRR / jumlah pelanggan berlangganan di akhir bulan.
 *  - Churn = pelanggan yang berhenti dalam bulan / pelanggan berlangganan di awal bulan.
 *  - Ditagih / diterima = invoice langganan (tanpa booster & instalasi), subtotal - diskon, tanpa PPN;
 *    ditagih menurut tanggal terbit, diterima menurut tanggal lunas.
 */
class BusinessKpiService
{
    private const EVER_ACTIVE = [Customer::STATUS_ACTIVE, Customer::STATUS_SUSPENDED, Customer::STATUS_WAITING_DISMANTLE, Customer::STATUS_DISMANTLED];

    public function build(int $months = 12, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $months = max(3, min(36, $months));
        $first = $now->startOfMonth()->subMonths($months - 1);

        $subs = $this->subscriptions();
        $billed = $this->invoiceSums('created_at', $first);
        $collected = $this->invoiceSums('paid_at', $first, paidOnly: true);

        $rows = [];
        for ($m = $first; $m->lte($now); $m = $m->addMonth()) {
            $start = $m->startOfMonth();
            $end = $m->isSameMonth($now) ? $now : $m->endOfMonth();
            $key = $m->format('Y-m');

            $atStart = $subs->filter(fn ($s) => $this->subscribedAt($s, $start->subSecond()));
            $atEnd = $subs->filter(fn ($s) => $this->subscribedAt($s, $end));
            $new = $subs->filter(fn ($s) => $s['start'] && $s['start']->betweenIncluded($start, $end))->count();
            $stopped = $subs->filter(fn ($s) => $s['start'] && $s['end'] && $s['end']->betweenIncluded($start, $end))->count();
            $mrr = round($atEnd->sum('price'), 2);

            $rows[$key] = [
                'month' => $m,
                'is_current' => $m->isSameMonth($now),
                'start_count' => $atStart->count(),
                'new' => $new,
                'stopped' => $stopped,
                'net' => $new - $stopped,
                'end_count' => $atEnd->count(),
                'churn_pct' => $atStart->count() > 0 ? round($stopped / $atStart->count() * 100, 1) : null,
                'mrr' => $mrr,
                'arpu' => $atEnd->count() > 0 ? round($mrr / $atEnd->count(), 0) : null,
                'billed' => round($billed[$key] ?? 0, 2),
                'collected' => round($collected[$key] ?? 0, 2),
            ];
        }

        $current = end($rows);
        $previous = count($rows) > 1 ? array_values($rows)[count($rows) - 2] : null;
        $closed = array_filter($rows, fn ($r) => ! $r['is_current']);
        $churnBase = array_sum(array_column($closed, 'start_count'));

        return [
            'months' => $months,
            'rows' => $rows,
            'current' => $current,
            'previous' => $previous,
            'mrrChange' => $previous && $previous['mrr'] > 0 ? round(($current['mrr'] - $previous['mrr']) / $previous['mrr'] * 100, 1) : null,
            // Rata-rata churn bulanan dari bulan-bulan yang sudah lewat (bulan berjalan belum lengkap)
            'avgChurn' => $churnBase > 0 ? round(array_sum(array_column($closed, 'stopped')) / $churnBase * 100, 1) : null,
            'totalNew' => array_sum(array_column($rows, 'new')),
            'totalStopped' => array_sum(array_column($rows, 'stopped')),
            'asOf' => $now,
        ];
    }

    /** Masa berlangganan tiap pelanggan + harga bulanan setelah diskon (tanpa PPN). */
    private function subscriptions(): Collection
    {
        return Customer::with('package:id,price')
            ->get(['id', 'status', 'package_id', 'activated_at', 'terminated_at', 'created_at', 'discount_type', 'discount_value'])
            ->map(function (Customer $c) {
                $start = $c->activated_at ?? (in_array($c->status, self::EVER_ACTIVE, true) ? $c->created_at : null);

                return [
                    'start' => $start ? CarbonImmutable::parse($start) : null,
                    'end' => $c->terminated_at ? CarbonImmutable::parse($c->terminated_at) : null,
                    'price' => $this->monthlyPrice($c),
                ];
            })
            ->filter(fn ($s) => $s['start'] !== null)
            ->values();
    }

    private function subscribedAt(array $s, CarbonImmutable $at): bool
    {
        return $s['start']->lte($at) && (! $s['end'] || $s['end']->gt($at));
    }

    public function monthlyPrice(Customer $c): float
    {
        $price = (float) ($c->package?->price ?? 0);
        $value = (float) ($c->discount_value ?? 0);
        $discount = match ($c->discount_type) {
            'percentage' => $price * $value / 100,
            'fixed' => min($value, $price),
            default => 0,
        };

        return round(max(0, $price - $discount), 2);
    }

    /** @return array<string, float> 'Y-m' => jumlah (subtotal - diskon) invoice langganan */
    private function invoiceSums(string $dateColumn, CarbonImmutable $from, bool $paidOnly = false): array
    {
        return Invoice::query()->recurringBill()
            ->when($paidOnly, fn ($q) => $q->where('status', 'paid'))
            ->whereNotNull($dateColumn)->where($dateColumn, '>=', $from)
            ->get([$dateColumn, 'subtotal', 'discount_amount', 'amount', 'tax_amount'])
            ->groupBy(fn ($i) => CarbonImmutable::parse($i->{$dateColumn})->format('Y-m'))
            ->map(fn ($g) => $g->sum(fn ($i) => $i->subtotal !== null
                ? (float) $i->subtotal - (float) $i->discount_amount
                : (float) $i->amount - (float) $i->tax_amount))
            ->all();
    }
}
