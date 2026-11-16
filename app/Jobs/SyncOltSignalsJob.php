<?php

namespace App\Jobs;

use App\Services\Network\NocService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sisa versi lama (sinkron ONU kini dijalankan langsung dari halaman NOC). Dipertahankan agar job lama yang
 * masih di antrean tetap aman diproses: memakai NocService::syncAll.
 */
class SyncOltSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function handle(NocService $noc): void
    {
        $noc->syncAll();
    }
}
