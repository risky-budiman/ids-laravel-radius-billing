<?php

namespace App\Console\Commands;

use App\Services\DatabaseDumpService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database';
    protected $description = 'Backup seluruh database (mysqldump .sql.gz) dengan rotasi & salinan opsional ke disk lain';

    public function handle(DatabaseDumpService $dumps): int
    {
        try {
            $r = $dumps->run();
            $msg = "{$r['file']} (" . number_format($r['size'] / 1048576, 2) . ' MB)' . ($r['remote'] ? " · disalin ke {$r['remote']}" : '');
            $dumps->recordStatus(true, $msg);
            $this->info("Backup selesai: {$msg}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $dumps->recordStatus(false, $e->getMessage());
            $this->error('Backup gagal: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
