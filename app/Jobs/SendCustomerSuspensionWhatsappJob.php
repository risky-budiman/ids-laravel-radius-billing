<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\WhatsappLog;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCustomerSuspensionWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customerId;
    public $reason;
    public $logId;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $customerId, string $reason = 'Tunggakan tagihan', ?int $logId = null)
    {
        $this->customerId = $customerId;
        $this->reason = $reason;
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $customer = Customer::find($this->customerId);

        if (!$customer || empty($customer->phone)) {
            if ($this->logId) {
                WhatsappLog::where('id', $this->logId)->update([
                    'status' => 'failed',
                    'error_reason' => 'Customer or phone number not found'
                ]);
            }
            return;
        }

        $template = WhatsappTemplate::where('type', 'suspension')->first();

        if (!$template || !$template->is_active) {
            return;
        }

        $message = $template->message;
        $amount = $customer->invoices()->where('status', 'unpaid')->sum('amount');
        $latestUnpaid = $customer->invoices()->where('status', 'unpaid')->latest()->first();
        $paymentLink = $latestUnpaid ? url('/pay/' . $latestUnpaid->invoice_number) : route('customer.login');
        $customerId = $customer->customer_code ?: ($customer->username ? explode('@', $customer->username)[0] : (string)$customer->id);

        $replace = [
            '{id_pelanggan}' => $customerId,
            '{customer_code}' => $customerId,
            '{username}' => $customer->username,
            '{pppoe_username}' => $customer->username,
            '{name}' => $customer->name,
            '{reason}' => $this->reason,
            '{amount}' => number_format($amount, 0, ',', '.'),
            '{payment_link}' => $paymentLink,
        ];

        $message = str_replace(array_keys($replace), array_values($replace), $message);

        if ($this->logId) {
            WhatsappLog::where('id', $this->logId)->update(['message' => $message]);
        }

        $waService->sendMessage($customer->phone, $message, $this->logId);
        Log::info("Suspension WA: Sent to {$customer->username} ({$customer->phone}). Reason: {$this->reason}");
    }
}
