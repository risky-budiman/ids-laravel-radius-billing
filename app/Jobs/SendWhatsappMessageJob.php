<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customerId;
    public $templateId;
    public $customDate;
    public $logId;
    public $timeout = 60; // 1 minute max per message
    
    /**
     * Create a new job instance.
     */
    public function __construct($customerId, $templateId, $customDate = null, $logId = null)
    {
        $this->customerId = $customerId;
        $this->templateId = $templateId;
        $this->customDate = $customDate;
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $customer = Customer::find($this->customerId);
        $template = WhatsappTemplate::find($this->templateId);

        if (!$customer || !$template || !$template->is_active) {
            if ($this->logId) {
                \App\Models\WhatsappLog::where('id', $this->logId)->update(['status' => 'failed', 'error_reason' => 'Customer or Template invalid/inactive']);
            }
            return;
        }

        if (empty($customer->phone)) {
            Log::warning("Broadcast WA: Customer {$customer->username} has no phone number.");
            if ($this->logId) {
                \App\Models\WhatsappLog::where('id', $this->logId)->update(['status' => 'failed', 'error_reason' => 'No phone number']);
            }
            return;
        }

        $message = $template->message;
        
        // Calculate dynamic variables if needed
        $amount = $customer->invoices()->where('status', 'unpaid')->sum('amount');
        $paymentLink = config('app.url') . '/portal';
        
        $formattedDate = date('d-m-Y H:i');
        if ($this->customDate) {
            $formattedDate = \Carbon\Carbon::parse($this->customDate)->format('d-m-Y H:i');
        }

        $replace = [
            '{id_pelanggan}' => $customer->username,
            '{name}' => $customer->name,
            '{amount}' => number_format($amount, 0, ',', '.'),
            '{payment_link}' => $paymentLink,
            '{tanggal}' => $formattedDate,
            // {periode}, {invoice_number}, {due_date}, {status}, {reason} are highly contextual
            // For general broadcast, we leave them or replace with generic info if we can
        ];

        $message = str_replace(array_keys($replace), array_values($replace), $message);
        
        if ($this->logId) {
            \App\Models\WhatsappLog::where('id', $this->logId)->update(['message' => $message]);
        }
                  
        $waService->sendMessage($customer->phone, $message, $this->logId);
        Log::info("Broadcast WA: Sent to {$customer->username} ({$customer->phone}). Template: {$template->name}");
    }
}
