<?php

namespace App\Jobs;

use App\Models\Olt;
use App\Services\Network\OltGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeprovisionOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $oltId;
    protected $onuIndex;
    protected $sn;

    /**
     * Create a new job instance.
     */
    public function __construct($oltId, $onuIndex, $sn)
    {
        $this->oltId = $oltId;
        $this->onuIndex = $onuIndex;
        $this->sn = $sn;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $olt = Olt::find($this->oltId);
        if (!$olt) {
            Log::error("Deprovisioning failed: OLT {$this->oltId} not found.");
            return;
        }

        // Parse index .shelf.slot.port.onu_id
        $parts = explode('.', ltrim($this->onuIndex, '.'));
        if (count($parts) < 4) {
            Log::error("Deprovisioning failed: Invalid ONU index format {$this->onuIndex}.");
            return;
        }

        $shelf = (int)$parts[0];
        $slot = (int)$parts[1];
        $port = (int)$parts[2];
        $onuId = (int)$parts[3];

        Log::info("Starting OLT Deprovisioning for SN: {$this->sn}");

        $gateway = new OltGateway($olt);
        $success = $gateway->deprovisionOnu($shelf, $slot, $port, $onuId);

        if ($success) {
            Log::info("Deprovisioning successful for SN: {$this->sn}");
        } else {
            Log::error("Deprovisioning FAILED for SN: {$this->sn}");
        }
    }
}
