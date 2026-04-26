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
    public $timeout = 120; // 2 minutes max
    
    /**
     * Create a new job instance.
     */
    public function __construct(Customer $customer, string $reason)
    {
        $this->customer = $customer;
        $this->reason = $reason;
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

        // Send WA Notification
        $template = \App\Models\WhatsappTemplate::where('type', 'suspension')->first();
        if ($template && $template->is_active) {
            $message = $template->message;
            $amount = $customer->invoices()->where('status', 'unpaid')->sum('amount');
            $paymentLink = config('app.url') . '/portal'; // General portal link since multiple invoices might exist

            $replace = [
                '{id_pelanggan}' => $customer->username,
                '{name}' => $customer->name,
                '{reason}' => $reason,
                '{amount}' => number_format($amount, 0, ',', '.'),
                '{payment_link}' => $paymentLink,
            ];

            $message = str_replace(array_keys($replace), array_values($replace), $message);
            $waService->sendMessage($customer->phone, $message);
        }
    }
}
