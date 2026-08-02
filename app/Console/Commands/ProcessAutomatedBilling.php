<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Jobs\GenerateCustomerInvoice;
use App\Jobs\ProcessCustomerSuspension;
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
    protected $description = 'Process automated daily billing, invoice generation, and suspension by dispatching jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Automated Billing Job Dispatcher...");
        Log::info("Automated Billing Dispatcher started.");
        
        $this->dispatchInvoices();
        $this->dispatchSuspensions();
        
        $this->info("Billing Dispatcher finished.");
        Log::info("Automated Billing Dispatcher finished.");
        return 0;
    }

    private function dispatchInvoices()
    {
        $this->info("Checking for new invoices to generate...");
        
        // Find customers whose billing_next_date is today or in the past (Only Active & Suspended)
        $customers = Customer::whereIn('status', [Customer::STATUS_ACTIVE, Customer::STATUS_SUSPENDED])
            ->whereNotNull('billing_next_date')
            ->whereDate('billing_next_date', '<=', now()->toDateString())
            ->get();

        foreach ($customers as $customer) {
            if ($this->option('dry-run')) {
                $this->line("Dry-run: Would dispatch invoice generation for {$customer->username}");
                continue;
            }

            GenerateCustomerInvoice::dispatch($customer);
            $this->info("Dispatched invoice generation job for {$customer->username}");
        }
    }

    private function dispatchSuspensions()
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
            $this->dispatchSuspensionJob($customer, "Tagihan Pasca Bayar belum lunas melewati jatuh tempo.");
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
            $this->dispatchSuspensionJob($customer, "Tagihan Prabayar Fixed belum lunas atau masa aktif habis.");
        }

        // 3. Prepaid Renewal Suspension
        $prepaidRenewalOverdue = Customer::where('is_active', true)
            ->where('billing_type', 'prepaid')
            ->where('billing_method', 'renewal')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now()->toDateString())
            ->get();

        foreach ($prepaidRenewalOverdue as $customer) {
            $this->dispatchSuspensionJob($customer, "Masa aktif Prabayar (Renewal) telah habis.");
        }
    }

    private function dispatchSuspensionJob($customer, $reason)
    {
        if ($this->option('dry-run')) {
            $this->line("Dry-run: Would dispatch suspension for {$customer->username}. Reason: {$reason}");
            return;
        }

        ProcessCustomerSuspension::dispatch($customer, $reason);
        $this->warn("Dispatched suspension job for {$customer->username}");
    }
}
