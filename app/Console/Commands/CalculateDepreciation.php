<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\Finance\DepreciationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Jurnal penyusutan aset tetap untuk satu bulan (bawaan: bulan berjalan). Dijadwalkan hari terakhir tiap bulan
 * pukul 23:00 bila "Penyusutan otomatis" diaktifkan di halaman Aset Tetap.
 *
 * Dulu: tidak pernah dijadwalkan, menyusutkan aset yang dibeli setelah bulan itu, mengabaikan tutup buku, dan
 * mengisi kolom jurnal yang tidak ada (nomor/status/keterangan baris diabaikan diam-diam).
 */
class CalculateDepreciation extends Command
{
    protected $signature = 'app:calculate-depreciation {--month= : Bulan YYYY-MM (bawaan bulan berjalan)} {--dry-run : Hanya tampilkan rencana}';
    protected $description = 'Jurnal penyusutan bulanan aset tetap (garis lurus)';

    public function handle(DepreciationService $service): int
    {
        try {
            $month = $this->option('month') ? CarbonImmutable::createFromFormat('Y-m', $this->option('month'))->startOfMonth() : now()->toImmutable()->startOfMonth();
        } catch (\Throwable) {
            $this->error('Format --month harus YYYY-MM.');

            return self::FAILURE;
        }
        $label = $month->locale('id')->translatedFormat('F Y');
        foreach ($service->plan($month) as $row) {
            $this->line(sprintf('%-14s %-30s %15s  %s', $row['asset']->asset_code, mb_substr($row['asset']->name, 0, 30),
                $row['amount'] ? number_format($row['amount'], 2, ',', '.') : '-', $row['note']));
        }
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        $r = $service->run($month);
        if ($r['count']) {
            ActivityLog::log('depreciation_posted', "Penyusutan {$label}: {$r['count']} aset, Rp " . number_format($r['total'], 0, ',', '.') . '.');
        }
        $this->info("Penyusutan {$label}: {$r['count']} aset dijurnal, total " . number_format($r['total'], 2, ',', '.') . '.');

        return self::SUCCESS;
    }
}
