<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\ImportRun;
use App\Services\CustomerImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Impor / pratinjau pelanggan untuk file besar, di antrean khusus "imports" (dijalankan jadwal tiap menit,
 * lihat routes/console.php) agar halaman tidak timeout. Tidak diulang otomatis: impor yang gagal di tengah
 * jalan diperiksa dulu (baris yang sudah masuk tidak digandakan karena impor ulang memakai mode lewati/perbarui).
 */
class RunCustomerImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3300;

    public function __construct(public string $runId)
    {
        $this->onQueue('imports');
    }

    public function handle(CustomerImportService $service): void
    {
        $run = ImportRun::find($this->runId);
        if (! $run || $run->status !== 'queued') {
            return;
        }
        $run->update(['status' => 'running', 'started_at' => now()]);
        $disk = Storage::disk('local');

        try {
            $result = $service->importFile($disk->path($run->path), [
                'duplicate_action' => $run->duplicate_action,
                'dry_run' => $run->mode === 'preview',
                'progress' => function (int $done, int $total) use ($run) {
                    $run->forceFill(['processed_rows' => $done, 'total_rows' => $total])->saveQuietly();
                },
            ]);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
            if ($run->mode === 'import') {
                $disk->delete($run->path);
            }

            return;
        }

        $run->update(['status' => 'done', 'result' => $result, 'processed_rows' => $run->total_rows ?: $result['total_rows'], 'finished_at' => now()]);
        if ($run->mode === 'import') {
            $disk->delete($run->path);
            ActivityLog::create([
                'user_id' => $run->user_id,
                'action' => 'customers_imported',
                'description' => "Impor pelanggan (latar belakang) dari file '{$run->file_name}': {$result['success']} dibuat, {$result['updated']} diperbarui, {$result['skipped']} dilewati.",
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportRun::whereKey($this->runId)->whereNotIn('status', ['done'])
            ->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
    }
}
