<?php

namespace App\Services\Reports;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekening koran Kas & Bank: saldo awal, mutasi masuk (CR) / keluar (DB) dengan saldo
 * berjalan, dan saldo akhir — per bulan, atau ringkasan 12 bulan untuk satu tahun.
 *
 * Seperti rekening koran bank, saldo dihitung MAJU dari mutasi:
 *   saldo awal periode = jumlah bersih semua mutasi sebelum awal periode
 * (saldo awal rekening dicatat sebagai transaksi "Saldo Awal" saat rekening dibuat).
 *
 * Kolom bank_accounts.balance hanyalah saldo tersimpan. Bila berbeda dari jumlah mutasi
 * (mis. transaksi pernah dihapus langsung di database), selisihnya dilaporkan lewat
 * drift() — tidak disebar diam-diam ke saldo awal setiap periode.
 */
class BankStatementService
{
    /** @param Collection<int, BankAccount> $accounts */
    public function statement(Collection $accounts, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $ids = $accounts->pluck('id')->all();
        $opening = $this->balanceAt($accounts, $start);

        $transactions = BankTransaction::with(['bankAccount:id,bank_name,account_name', 'category:id,code,name', 'creator:id,name'])
            ->whereIn('bank_account_id', $ids)
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $balance = $opening;
        $rows = $transactions->map(function (BankTransaction $t) use (&$balance) {
            $in = $t->type === 'deposit' ? (float) $t->amount : 0.0;
            $out = $t->type === 'deposit' ? 0.0 : (float) $t->amount;
            $balance = round($balance + $in - $out, 2);

            return [
                'id' => $t->id,
                'date' => $t->transaction_date,
                'account' => $t->bankAccount ? trim($t->bankAccount->bank_name . ' · ' . $t->bankAccount->account_name, ' ·') : '-',
                'description' => $t->description,
                'reference' => $t->reference_number,
                'category' => $t->category ? trim(($t->category->code ? $t->category->code . ' ' : '') . $t->category->name) : null,
                'creator' => $t->creator?->name,
                'in' => $in,
                'out' => $out,
                'balance' => $balance,
            ];
        });

        $totalIn = round($rows->sum('in'), 2);
        $totalOut = round($rows->sum('out'), 2);

        return [
            'start' => $start,
            'end' => $end,
            'opening' => $opening,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'count_in' => $rows->where('in', '>', 0)->count(),
            'count_out' => $rows->where('out', '>', 0)->count(),
            'closing' => round($opening + $totalIn - $totalOut, 2),
            'rows' => $rows,
        ];
    }

    /** Ringkasan 12 bulan (tanpa rincian mutasi) untuk rekening koran tahunan. */
    public function yearSummary(Collection $accounts, int $year): array
    {
        $ids = $accounts->pluck('id')->all();
        $yearStart = CarbonImmutable::create($year, 1, 1)->startOfYear();
        $balance = $this->balanceAt($accounts, $yearStart);

        $perMonth = BankTransaction::whereIn('bank_account_id', $ids)
            ->whereBetween('transaction_date', [$yearStart, $yearStart->endOfYear()])
            ->get(['type', 'amount', 'transaction_date'])
            ->groupBy(fn ($t) => (int) $t->transaction_date->month);

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $tx = $perMonth->get($m, collect());
            $in = round($tx->where('type', 'deposit')->sum('amount'), 2);
            $out = round($tx->where('type', '!=', 'deposit')->sum('amount'), 2);
            $start = $yearStart->month($m)->startOfMonth();
            $months[$m] = [
                'start' => $start,
                'future' => $start->isFuture(),
                'opening' => $balance,
                'total_in' => $in,
                'total_out' => $out,
                'count' => $tx->count(),
                'closing' => round($balance + $in - $out, 2),
            ];
            $balance = $months[$m]['closing'];
        }

        return [
            'start' => $yearStart,
            'end' => $yearStart->endOfYear(),
            'opening' => $months[1]['opening'],
            'total_in' => round(array_sum(array_column($months, 'total_in')), 2),
            'total_out' => round(array_sum(array_column($months, 'total_out')), 2),
            'count' => array_sum(array_column($months, 'count')),
            'closing' => $months[12]['closing'],
            'months' => $months,
        ];
    }

    /** Saldo gabungan rekening tepat sebelum $moment = mutasi bersih sebelum $moment. */
    public function balanceAt(Collection $accounts, CarbonImmutable $moment): float
    {
        return round((float) BankTransaction::whereIn('bank_account_id', $accounts->pluck('id')->all())
            ->where('transaction_date', '<', $moment)
            ->sum(DB::raw("CASE WHEN type = 'deposit' THEN amount ELSE -amount END")), 2);
    }

    /**
     * Rekening yang saldo tersimpannya tidak sama dengan jumlah mutasinya.
     *
     * @return array<int, array{account: BankAccount, stored: float, from_mutations: float, difference: float}>
     */
    public function drift(Collection $accounts): array
    {
        $net = BankTransaction::whereIn('bank_account_id', $accounts->pluck('id')->all())
            ->groupBy('bank_account_id')
            ->selectRaw("bank_account_id, SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) AS net")
            ->pluck('net', 'bank_account_id');

        $out = [];
        foreach ($accounts as $a) {
            $fromMutations = round((float) ($net[$a->id] ?? 0), 2);
            $diff = round((float) $a->balance - $fromMutations, 2);
            if (abs($diff) >= 0.01) {
                $out[] = ['account' => $a, 'stored' => (float) $a->balance, 'from_mutations' => $fromMutations, 'difference' => $diff];
            }
        }

        return $out;
    }
}
