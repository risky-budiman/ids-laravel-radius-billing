<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\LogRetention;
use Illuminate\Console\Command;

/** Hapus log lama sesuai Log Aktivitas › Masa simpan log (bawaan: tidak ada yang dihapus). */
class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune {--dry-run : Hanya tampilkan jumlah yang akan dihapus}';
    protected $description = 'Hapus log aktivitas, WhatsApp, login PPPoE & notifikasi lama sesuai masa simpan';

    public function handle(LogRetention $retention): int
    {
        $plan = $retention->plan();
        foreach ($plan as $p) {
            $this->line(sprintf('%-40s simpan %-10s %s', $p['label'], $p['keep'], $p['cutoff'] ? "akan dihapus: {$p['count']}" : '—'));
        }
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        $done = array_filter($retention->prune());
        if ($done) {
            $labels = collect($plan)->pluck('label', 'key');
            ActivityLog::log('logs_pruned', 'Log lama dihapus sesuai masa simpan: ' . collect($done)->map(fn ($n, $k) => "{$labels[$k]} {$n}")->implode(', ') . '.');
            $this->info('Dihapus: ' . array_sum($done) . ' baris.');
        } else {
            $this->info('Tidak ada log yang perlu dihapus.');
        }

        return self::SUCCESS;
    }
}
