<?php

namespace App\Jobs;

use App\Services\Network\NocService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sisa versi lama (pencarian ONU kini dijalankan langsung dari halaman NOC). Dipertahankan agar job lama yang
 * masih di antrean tetap aman diproses: memakai logika baru NocService::unregistered.
 */
class ScanOltDiscoveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function handle(NocService $noc): void
    {
        $noc->unregistered(true);
    }
}
