<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Radius\Nas;
use App\Services\RadiusCoAService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutomatedBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-billing {--dry-run : Only show what would be done}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automated daily billing, invoice generation, and suspension';

    /**
     * Execute the console command.
     */
    public function handle(RadiusCoAService $coaService, WhatsAppService $waService)
    {
        $this->info("Starting Automated Billing Processor...");
        
        $this->generateInvoices($waService);
        $this->processSuspensions($coaService, $waService);
        
        $this->info("Billing Processor finished.");
        return 0;
    }

    private function generateInvoices($waService)
    {
        $this->info("Checking for new invoices to generate...");
        
        // Find customers whose billing_next_date is today or in the past
        $customers = Customer::where('is_active', true)
            ->whereNotNull('billing_next_date')
            ->where('billing_next_date', '<=', now()->toDateString())
            ->get();

        foreach ($customers as $customer) {
            if ($this->option('dry-run')) {
                $this->line("Dry-run: Would generate invoice for {$customer->name} ({$customer->username})");
                continue;
            }

            // Generate Invoice
            $price = $customer->package->price;
            $nextDate = $customer->billing_next_date ?? now();
            
            if ($customer->billing_type === 'postpaid' && $customer->billing_method === 'cycle') {
                // For Postpaid Cycle, the invoice generated on the 1st covers the PREVIOUS month
                $startDate = $nextDate->copy()->subMonth()->startOfMonth();
                $endDate = $nextDate->copy()->subDay(); // Last day of previous month
            } else {
                // Default for Prepaid or other types
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

            $this->info("Generated invoice {$invoice->invoice_number} for {$customer->username}");

            // Update next billing schedule
            $customer->syncBillingDates();

            // Send WhatsApp Notification
            $this->sendInvoiceNotification($customer, $invoice, $waService);
        }
    }

    private function processSuspensions($coaService, $waService)
    {
        $this->info("Checking for customers needing suspension...");

        // 1. Postpaid Suspension (Overdue Unpaid Invoices)
        $postpaidOverdue = Customer::where('is_active', true)
            ->where('billing_type', 'postpaid')
            ->whereNotNull('billing_due_date')
            ->where('billing_due_date', '<', now()->toDateString())
            ->whereHas('invoices', function($q) {
                $q->where('status', 'unpaid');
            })
            ->get();

        foreach ($postpaidOverdue as $customer) {
            $this->suspendCustomer($customer, "Tagihan Pasca Bayar belum lunas melewati jatuh tempo.", $coaService, $waService);
        }

        // 2. Prepaid Fixed Suspension
        $prepaidFixedOverdue = Customer::where('is_active', true)
            ->where('billing_type', 'prepaid')
            ->where('billing_method', 'fixed')
            ->whereNotNull('billing_due_date')
            ->where('billing_due_date', '<', now()->toDateString())
            ->whereHas('invoices', function($q) {
                $q->where('status', 'unpaid');
            })
            ->get();
            
        foreach ($prepaidFixedOverdue as $customer) {
            $this->suspendCustomer($customer, "Tagihan Prabayar Fixed belum lunas atau masa aktif habis.", $coaService, $waService);
        }

        // 3. Prepaid Renewal Suspension
        $prepaidRenewalOverdue = Customer::where('is_active', true)
            ->where('billing_type', 'prepaid')
            ->where('billing_method', 'renewal')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now()->toDateString())
            ->get();

        foreach ($prepaidRenewalOverdue as $customer) {
            $this->suspendCustomer($customer, "Masa aktif Prabayar (Renewal) telah habis.", $coaService, $waService);
        }
    }

    private function suspendCustomer($customer, $reason, $coaService, $waService)
    {
        if ($this->option('dry-run')) {
            $this->line("Dry-run: Would suspend {$customer->username}. Reason: {$reason}");
            return;
        }

        $this->warn("Suspending customer: {$customer->username}");
        
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
        $message = "Halo *{$customer->name}*,\n\n" .
                   "Layanan internet Anda sementara ditangguhkan (SUSPENDED).\n" .
                   "Alasan: {$reason}\n\n" .
                   "Silakan melakukan pembayaran untuk mengaktifkan kembali layanan. Terima kasih.";
                   
        $waService->sendMessage($customer->phone, $message);
    }

    private function sendInvoiceNotification($customer, $invoice, $waService)
    {
        $message = "Halo *{$customer->name}*,\n\n" .
                  "Tagihan internet Anda untuk nomor *{$invoice->invoice_number}* sebesar *Rp " . number_format($invoice->amount, 0, ',', '.') . "* telah terbit.\n" .
                  "Jatuh tempo pada: *{$invoice->due_date->format('d-m-Y')}*.\n\n" .
                  "Silakan segera lakukan pembayaran melalui portal pelanggan. Terima kasih.";
                  
        $waService->sendMessage($customer->phone, $message);
    }
}
