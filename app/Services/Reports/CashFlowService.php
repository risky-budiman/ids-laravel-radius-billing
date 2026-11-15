<?php

namespace App\Services\Reports;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Arus kas metode langsung dari jurnal akun Kas & Bank (1001 dan turunannya) — dipakai laporan web,
 * ekspor Excel dan API.
 *
 * Setiap baris kas diklasifikasikan per AKUN LAWAN dengan nominal akun lawan itu sendiri (jurnal yang
 * dipecah, mis. pendapatan + PPN, tidak terhitung ganda). Kode akun per aktivitas bisa diatur
 * (Pengaturan klasifikasi di laporan Cash Flow); kode yang terdaftar didahulukan, sisanya menurut
 * tipe akun: pendapatan -> operasional masuk, beban -> operasional keluar, ekuitas/kewajiban ->
 * pendanaan masuk. Yang tidak masuk mana pun tampil sebagai "lainnya" agar saldo tetap cocok.
 */
class CashFlowService
{
    /** Bawaan: pembayaran invoice dijurnal Dr Bank / Cr 1104 Piutang, jadi 1104 = penerimaan dari pelanggan. */
    public const DEFAULTS = [
        'cashflow_operating_in_codes' => '1103,1104,2102',
        'cashflow_operating_out_codes' => '1105,1106,2101,2103,2104,2105',
        'cashflow_investing_codes' => '1201,1202',
    ];

    public const LABELS = [
        'cashflow_operating_in_codes' => 'Operasional — kas masuk',
        'cashflow_operating_out_codes' => 'Operasional — kas keluar',
        'cashflow_investing_codes' => 'Investasi — kas keluar',
    ];

    /** @return array<string, list<string>> */
    public function codes(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $out[$key] = self::parse((string) get_setting($key, $default));
        }

        return $out;
    }

    /** @param array<string, string> $values */
    public function saveCodes(array $values): void
    {
        foreach (self::DEFAULTS as $key => $default) {
            $codes = self::parse($values[$key] ?? '');
            Setting::updateOrCreate(['key' => $key], ['value' => implode(',', $codes)]);
        }
        Cache::forget('app_settings');
    }

    public static function parse(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', $value)))));
    }

    public function compute(string $startDate, string $endDate): array
    {
        $codes = $this->codes();
        $cashAccounts = ChartOfAccount::where('code', '1001')
            ->orWhere('parent_id', fn ($q) => $q->select('id')->from('chart_of_accounts')->where('code', '1001'))
            ->pluck('id');

        $journals = Journal::whereBetween('date', [$startDate, $endDate])
            ->whereHas('items', fn ($q) => $q->whereIn('account_id', $cashAccounts))
            ->with('items.account')
            ->get();

        $operatingIn = $operatingOut = $investingOut = $financingIn = $otherIn = $otherOut = 0.0;

        foreach ($journals as $journal) {
            $cashItems = $journal->items->whereIn('account_id', $cashAccounts);
            $offsets = $journal->items->whereNotIn('account_id', $cashAccounts);
            $netCash = (float) $cashItems->sum('debit') - (float) $cashItems->sum('credit');
            if (abs($netCash) < 0.005 || $offsets->isEmpty()) {
                continue;   // transfer antar kas/bank: tidak mengubah total kas
            }

            $classified = 0.0;
            foreach ($offsets as $offset) {
                $type = $offset->account?->type;
                $code = (string) $offset->account?->code;
                if ($netCash > 0) {   // kas masuk: sisi kredit akun lawan
                    $amount = (float) $offset->credit - (float) $offset->debit;
                    if (in_array($code, $codes['cashflow_operating_in_codes'], true) || ($type === 'income' && ! in_array($code, $codes['cashflow_investing_codes'], true))) {
                        $operatingIn += $amount;
                    } elseif ($type === 'equity' || $type === 'liability') {
                        $financingIn += $amount;
                    } else {
                        continue;
                    }
                } else {              // kas keluar: sisi debit akun lawan
                    $amount = (float) $offset->debit - (float) $offset->credit;
                    if (in_array($code, $codes['cashflow_investing_codes'], true)) {
                        $investingOut += $amount;
                    } elseif (in_array($code, $codes['cashflow_operating_out_codes'], true) || $type === 'expense') {
                        $operatingOut += $amount;
                    } else {
                        continue;
                    }
                }
                $classified += $amount;
            }

            $rest = abs($netCash) - $classified;
            if (abs($rest) >= 0.005) {
                $netCash > 0 ? $otherIn += $rest : $otherOut += $rest;
            }
        }

        $netOperating = $operatingIn - $operatingOut;
        $netInvesting = -$investingOut;
        $netFinancing = $financingIn;
        $netOther = $otherIn - $otherOut;
        $netIncrease = $netOperating + $netInvesting + $netFinancing + $netOther;

        $cashBalance = fn (string $op, string $date) => (float) JournalItem::whereIn('account_id', $cashAccounts)
            ->whereHas('journal', fn ($q) => $q->where('date', $op, $date))
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS bal')->value('bal');

        $openingCash = $cashBalance('<', $startDate);
        $closingCash = $cashBalance('<=', $endDate);

        return compact(
            'operatingIn', 'operatingOut', 'investingOut', 'financingIn', 'otherIn', 'otherOut',
            'netOperating', 'netInvesting', 'netFinancing', 'netOther', 'netIncrease',
            'openingCash', 'closingCash'
        );
    }
}
