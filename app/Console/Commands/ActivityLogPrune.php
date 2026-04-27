<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ActivityLogPrune extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:activity-log-prune {--days=90 : The number of days of logs to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old activity logs from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $date = Carbon::now()->subDays($days);

        $count = ActivityLog::where('created_at', '<', $date)->count();

        if ($count === 0) {
            $this->info("No logs found older than {$days} days.");
            return;
        }

        if ($this->confirm("Found {$count} logs older than {$days} days. Do you want to delete them?")) {
            ActivityLog::where('created_at', '<', $date)->delete();
            $this->info("Successfully deleted {$count} activity logs.");
        }
    }
}
