<?php

namespace App\Services\Finance;

use App\Models\FixedAsset;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Penyusutan aset tetap garis lurus: (harga perolehan − nilai sisa) ÷ umur manfaat (bulan), mulai bulan perolehan.
 * Satu jurnal per aset per bulan (Dr akun beban penyusutan / Cr akumulasi penyusutan), tanggal akhir bulan,
 * aman dijalankan berulang. Bulan yang sudah tutup buku tidak dijurnal. Otomatis bila diaktifkan (bawaan mati).
 */
class DepreciationService
{
    public const KEY = 'depreciation_auto_enabled';

    public static function enabled(): bool
    {
        return Setting::where('key', self::KEY)->value('value') === '1';
    }

    public static function setEnabled(bool $on): void
    {
        Setting::updateOrCreate(['key' => self::KEY], ['value' => $on ? '1' : '0', 'type' => 'boolean']);
        Cache::forget('app_settings');
    }

    public static function reference(FixedAsset $asset): string
    {
        return 'Depresiasi Aset: ' . $asset->asset_code;
    }

    public static function monthly(FixedAsset $asset): float
    {
        $base = (float) $asset->purchase_price - (float) $asset->salvage_value;

        return $asset->useful_life_months > 0 && $base > 0 ? round($base / $asset->useful_life_months, 2) : 0.0;
    }

    public static function remaining(FixedAsset $asset): float
    {
        return round(max(0, (float) $asset->purchase_price - (float) $asset->salvage_value - (float) $asset->accumulated_depreciation), 2);
    }

    /**
     * Rencana penyusutan satu bulan untuk semua aset.
     *
     * @return list<array{asset: FixedAsset, amount: float, status: string, note: string}>
     *         status: due | posted | not_yet | done | inactive | config | closed
     */
    public function plan(CarbonImmutable $month): array
    {
        $end = $month->endOfMonth()->startOfDay();
        $posted = Journal::whereDate('date', $end->toDateString())->where('reference', 'like', 'Depresiasi Aset: %')
            ->pluck('reference')->flip();
        $locked = is_accounting_locked($end->toDateString());
        $rows = [];
        foreach (FixedAsset::with(['depreciationAccount:id,code,name,type', 'accumulatedAccount:id,code,name,type'])->orderBy('asset_code')->get() as $asset) {
            $monthly = self::monthly($asset);
            $amount = min($monthly, self::remaining($asset));
            [$status, $note] = match (true) {
                $posted->has(self::reference($asset)) => ['posted', 'Sudah dijurnal untuk bulan ini'],
                $asset->status !== 'active' => ['inactive', 'Aset tidak aktif'],
                $asset->purchase_date->copy()->startOfMonth()->gt($end) => ['not_yet', 'Dibeli setelah bulan ini'],
                $amount <= 0 => ['done', 'Sudah habis disusutkan'],
                $asset->accumulated_account_id === $asset->asset_account_id || ($asset->depreciationAccount && $asset->depreciationAccount->type !== 'expense')
                    => ['config', 'Periksa akun: akumulasi harus akun kontra (mis. 1290), beban harus akun beban'],
                $locked => ['closed', 'Periode sudah tutup buku'],
                default => ['due', $amount < $monthly ? 'Penyusutan terakhir (sisa nilai)' : 'Siap dijurnal'],
            };
            $rows[] = ['asset' => $asset, 'amount' => $status === 'due' ? $amount : ($status === 'posted' ? $monthly : 0.0),
                'status' => $status, 'note' => $note];
        }

        return $rows;
    }

    /** Jurnal penyusutan untuk aset yang siap pada bulan itu. @return array{count: int, total: float} */
    public function run(CarbonImmutable $month): array
    {
        $end = $month->endOfMonth()->toDateString();
        $count = 0;
        $total = 0.0;
        foreach ($this->plan($month) as $row) {
            if ($row['status'] !== 'due') {
                continue;
            }
            DB::transaction(function () use ($row, $end, &$count, &$total) {
                // Cek ulang di dalam transaksi: dua proses bersamaan tidak boleh menjurnal dua kali
                $asset = FixedAsset::whereKey($row['asset']->id)->lockForUpdate()->first();
                if (Journal::where('reference', self::reference($asset))->whereDate('date', $end)->exists()) {
                    return;
                }
                $amount = min(self::monthly($asset), self::remaining($asset));
                if ($amount <= 0) {
                    return;
                }
                $journal = Journal::create([
                    'date' => $end,
                    'reference' => self::reference($asset),
                    'description' => mb_substr('Beban penyusutan ' . CarbonImmutable::parse($end)->locale('id')->translatedFormat('F Y') . ' — ' . $asset->name, 0, 255),
                    // Jadwal (tanpa login): dicatat atas nama administrator pertama, seperti jurnal otomatis lain
                    'created_by' => auth()->id() ?? User::where('role', User::ROLE_ADMINISTRATOR)->orderBy('id')->value('id'),
                ]);
                JournalItem::create(['journal_id' => $journal->id, 'account_id' => $asset->depreciation_account_id, 'debit' => $amount, 'credit' => 0]);
                JournalItem::create(['journal_id' => $journal->id, 'account_id' => $asset->accumulated_account_id, 'debit' => 0, 'credit' => $amount]);
                $asset->forceFill(['accumulated_depreciation' => round((float) $asset->accumulated_depreciation + $amount, 2)])->save();
                $count++;
                $total += $amount;
            });
        }

        return ['count' => $count, 'total' => round($total, 2)];
    }
}
