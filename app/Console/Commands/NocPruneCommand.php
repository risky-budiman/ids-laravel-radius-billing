<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerSignalLog;
use App\Models\OltStatusLog;

class NocPruneCommand extends Command
{
    protected $signature = 'noc:prune {days=30}';
    protected $description = 'Cleanup old NOC monitoring logs to prevent database bloating';

    public function handle()
    {
        $days = $this->argument('days');
        $date = now()->subDays($days);

        $this->info("Cleaning up NOC logs older than {$days} days ({$date->toDateString()})...");

        // Prune Customer Signal Logs
        $count = CustomerSignalLog::where('created_at', '<', $date)->delete();
        $this->info("Deleted {$count} customer signal logs.");

        // Prune OLT Status Logs
        $countOlt = OltStatusLog::where('created_at', '<', $date)->delete();
        $this->info("Deleted {$countOlt} OLT status logs.");

        $this->info("Cleanup completed.");
    }
}
