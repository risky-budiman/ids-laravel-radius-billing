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

class UpdateOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $oltId;
    protected $onuIndex;
    protected $action; // 'suspend', 'resume', 'update_speed'
    protected $package;

    /**
     * Create a new job instance.
     */
    public function __construct($oltId, $onuIndex, $action, $package = null)
    {
        $this->oltId = $oltId;
        $this->onuIndex = $onuIndex;
        $this->action = $action;
        $this->package = $package;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $olt = Olt::find($this->oltId);
        if (!$olt) return;

        $gateway = new OltGateway($olt);

        $parts = explode('.', ltrim($this->onuIndex, '.'));
        if (count($parts) < 4) return;

        $shelf = (int)$parts[0];
        $slot = (int)$parts[1];
        $port = (int)$parts[2];
        $onuId = (int)$parts[3];

        Log::info("Executing OLT {$this->action} for ONU index: {$this->onuIndex}");

        switch ($this->action) {
            case 'suspend':
                $gateway->suspendOnu($shelf, $slot, $port, $onuId);
                break;
            case 'resume':
                $gateway->resumeOnu($shelf, $slot, $port, $onuId);
                break;
            case 'update_speed':
                if ($this->package) {
                    $gateway->updateOnuProfile($shelf, $slot, $port, $onuId, $this->package);
                }
                break;
        }
    }
}
