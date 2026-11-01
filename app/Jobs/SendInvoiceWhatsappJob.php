<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\WhatsappLog;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoiceId;
    public $logId;
    public $timeout = 60; // 1 minute max per message

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId, ?int $logId = null)
    {
        $this->invoiceId = $invoiceId;
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $invoice = Invoice::with(['customer', 'customer.package'])->find($this->invoiceId);

        if (!$invoice || !$invoice->customer) {
            if ($this->logId) {
                WhatsappLog::where('id', $this->logId)->update([
                    'status' => 'failed',
                    'error_reason' => 'Invoice or Customer not found'
                ]);
            }
            return;
        }

        $customer = $invoice->customer;

        if (empty($customer->phone)) {
            Log::warning("Invoice WA: Customer {$customer->username} has no phone number for INV #{$invoice->invoice_number}.");
            if ($this->logId) {
                WhatsappLog::where('id', $this->logId)->update([
                    'status' => 'failed',
                    'error_reason' => 'Customer phone number is empty'
                ]);
            }
            return;
        }

        $template = WhatsappTemplate::where('type', 'invoice_generated')->first();

        $customerId = $customer->customer_code ?: ($customer->username ? explode('@', $customer->username)[0] : (string)$customer->id);
        $periode = $invoice->period_start ? ($invoice->period_start->format('d M') . ' - ' . $invoice->period_end->format('d M Y')) : now()->format('F Y');
        $paymentLink = url('/pay/' . $invoice->invoice_number);

        if ($template && $template->is_active) {
            $message = $template->message;
            $replace = [
                '{id_pelanggan}' => $customerId,
                '{customer_code}' => $customerId,
                '{username}' => $customer->username,
                '{pppoe_username}' => $customer->username,
                '{name}' => $customer->name,
                '{periode}' => $periode,
                '{amount}' => number_format($invoice->amount, 0, ',', '.'),
                '{invoice_number}' => $invoice->invoice_number,
                '{due_date}' => $invoice->due_date ? $invoice->due_date->format('d-m-Y') : '-',
                '{payment_link}' => $paymentLink,
            ];
            $message = str_replace(array_keys($replace), array_values($replace), $message);
        } else {
            // Default fallback notification format
            $message = "Halo *{$customer->name}*,\n\n" .
                      "Tagihan internet Anda untuk nomor *{$invoice->invoice_number}* sebesar *Rp " . number_format($invoice->amount, 0, ',', '.') . "* telah terbit.\n\n" .
                      "Periode: {$periode}\n" .
                      "Jatuh Tempo: " . ($invoice->due_date ? $invoice->due_date->format('d-m-Y') : '-') . "\n\n" .
                      "Silakan bayar melalui link resmi berikut:\n" .
                      "{$paymentLink}\n\n" .
                      "Terima kasih.";
        }

        if ($this->logId) {
            WhatsappLog::where('id', $this->logId)->update(['message' => $message]);
        }

        $waService->sendMessage($customer->phone, $message, $this->logId);
        Log::info("Invoice WA: Dispatched to {$customer->username} ({$customer->phone}) for INV #{$invoice->invoice_number}");
    }
}

