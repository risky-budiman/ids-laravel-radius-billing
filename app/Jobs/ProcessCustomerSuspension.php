<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Radius\Nas;
use App\Services\RadiusCoAService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCustomerSuspension implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customer;
    public $reason;
    public $waDelay;
    public $timeout = 120; // 2 minutes max
    
    /**
     * Create a new job instance.
     */
    public function __construct(Customer $customer, string $reason, int $waDelay = 0)
    {
        $this->customer = $customer;
        $this->reason = $reason;
        $this->waDelay = $waDelay;
    }

    /**
     * Execute the job.
     */
    public function handle(RadiusCoAService $coaService, WhatsAppService $waService): void
    {
        $customer = $this->customer;
        $reason = $this->reason;

        // Double check if customer is still active
        // They might have paid while waiting in the queue
        if (!$customer->is_active) {
            return;
        }

        Log::warning("Suspending customer: {$customer->username}. Reason: {$reason}");
        
        $customer->update([
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED
        ]);

        // CoA Disconnect if possible
        $nas = Nas::first();
        if ($nas) {
            $coaService->disconnect($nas->nasname, $nas->secret, $customer->username);
        }

        // Send WA Notification via Queued Job with Anti-Ban Delay
        if (!empty($customer->phone)) {
            $job = \App\Jobs\SendCustomerSuspensionWhatsappJob::dispatch($customer->id, $reason);
            if ($this->waDelay > 0) {
                $job->delay(now()->addSeconds($this->waDelay));
            }
        }
    }
}
