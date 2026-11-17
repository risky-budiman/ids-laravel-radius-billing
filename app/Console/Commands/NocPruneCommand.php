<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerSignalLog;
use App\Models\OltStatusLog;

class NocPruneCommand extends Command
{
    protected $signature = 'noc:prune {days=30}';
    protected $description = 'Hapus riwayat sinyal ONU & status OLT lama agar database tidak membengkak';

    public function handle()
    {
        $days = $this->argument('days');
        $date = now()->subDays($days);

        $this->info("Menghapus riwayat NOC lebih lama dari {$days} hari (sebelum {$date->toDateString()})…");

        // Prune Customer Signal Logs
        $count = CustomerSignalLog::where('created_at', '<', $date)->delete();
        $this->info("{$count} riwayat sinyal pelanggan dihapus.");

        // Prune OLT Status Logs
        $countOlt = OltStatusLog::where('created_at', '<', $date)->delete();
        $this->info("{$countOlt} riwayat status OLT dihapus.");

        $this->info("Pembersihan selesai.");
    }
}
