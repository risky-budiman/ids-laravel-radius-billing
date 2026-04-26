<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCustomerInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customer;
    public $timeout = 120; // 2 minutes max
    
    /**
     * Create a new job instance.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $customer = $this->customer;
        
        // Double check condition in case things changed while in queue
        if (!$customer->is_active || !$customer->billing_next_date || $customer->billing_next_date > now()->toDateString()) {
            return;
        }

        // Generate Invoice
        $price = $customer->package->price;
        $nextDate = $customer->billing_next_date ?? now();
        
        if ($customer->billing_type === 'postpaid' && $customer->billing_method === 'cycle') {
            $startDate = $nextDate->copy()->subMonth()->startOfMonth();
            $endDate = $nextDate->copy()->subDay();
        } else {
            $startDate = $nextDate->copy();
            $endDate = $startDate->copy()->addMonth()->subDay();
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'billing_period' => '1 Month',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'amount' => $price,
            'status' => 'unpaid',
            'due_date' => $customer->billing_due_date,
            'notes' => 'Tagihan otomatis skema ' . ucfirst($customer->billing_method),
        ]);

        Log::info("Generated invoice {$invoice->invoice_number} for {$customer->username}");

        // Update next billing schedule
        $customer->syncBillingDates();

        // Send WhatsApp Notification
        $this->sendInvoiceNotification($customer, $invoice, $waService);
    }

    private function sendInvoiceNotification($customer, $invoice, $waService)
    {
        $template = \App\Models\WhatsappTemplate::where('type', 'invoice_generated')->first();
        
        if (!$template || !$template->is_active) {
            return; // Don't send if template is inactive or missing
        }

        $message = $template->message;
        $periode = $invoice->period_start->format('d M') . ' - ' . $invoice->period_end->format('d M Y');
        $paymentLink = config('app.url') . '/pay/' . $invoice->invoice_number; // Assuming you have a payment route or just general portal

        $replace = [
            '{id_pelanggan}' => $customer->username,
            '{name}' => $customer->name,
            '{periode}' => $periode,
            '{amount}' => number_format($invoice->amount, 0, ',', '.'),
            '{invoice_number}' => $invoice->invoice_number,
            '{due_date}' => $invoice->due_date->format('d-m-Y'),
            '{payment_link}' => $paymentLink,
        ];

        $message = str_replace(array_keys($replace), array_values($replace), $message);
                  
        $waService->sendMessage($customer->phone, $message);
    }
}
