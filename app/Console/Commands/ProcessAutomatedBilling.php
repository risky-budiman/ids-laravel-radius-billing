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
        // Terisolir tetap ditagih; batal, menunggu dismantle & dicabut tidak (Customer::BILLABLE_STATUSES)
        $customers = Customer::whereIn('status', Customer::BILLABLE_STATUSES)
            ->whereNotNull('billing_next_date')
            ->whereDate('billing_next_date', '<=', now()->toDateString())
            ->get();

        $accumulatedWaDelay = 0;

        foreach ($customers as $customer) {
            if ($this->option('dry-run')) {
                $this->line("Dry-run: Would dispatch invoice generation for {$customer->username}");
                continue;
            }

            GenerateCustomerInvoice::dispatch($customer, $accumulatedWaDelay);
            $this->info("Dispatched invoice generation job for {$customer->username} (WA Delay: {$accumulatedWaDelay}s)");

            if (!empty($customer->phone)) {
                $accumulatedWaDelay += \App\Services\WhatsAppService::getRandomDelay();
            }
        }
    }

    private function dispatchSuspensions()
    {
        $this->info("Checking for customers needing suspension...");
        $accumulatedDelay = 0;
        $today = now()->toDateString();

        // Isolir berdasarkan jatuh tempo INVOICE yang belum dibayar, bukan customers.billing_due_date.
        // billing_due_date sudah dimajukan ke siklus berikutnya saat invoice diterbitkan, sehingga
        // perbandingan lama ("billing_due_date < hari ini") tidak pernah terpenuhi selama invoice
        // terus terbit tiap bulan — pelanggan yang tidak membayar tidak pernah diisolir.
        $overdue = Customer::where('is_active', true)
            ->where('status', Customer::STATUS_ACTIVE)
            ->where(function ($q) use ($today) {
                $q->whereHas('invoices', fn ($i) => $i->suspendableRecurring())   // lewat jatuh tempo + masa tenggang
                  // Renewal: masa aktif habis walau invoice belum/tidak terbit.
                  ->orWhere(fn ($r) => $r->where('billing_type', 'prepaid')
                      ->where('billing_method', 'renewal')
                      ->whereNotNull('expired_at')
                      ->whereDate('expired_at', '<', $today));
            })
            ->get();

        foreach ($overdue as $customer) {
            $reason = match (true) {
                $customer->billing_type === 'prepaid' && $customer->billing_method === 'renewal' => 'Masa aktif Prabayar (Renewal) telah habis.',
                $customer->billing_type === 'prepaid' => 'Tagihan Prabayar Fixed belum lunas atau masa aktif habis.',
                default => 'Tagihan Pasca Bayar belum lunas melewati jatuh tempo.',
            };
            $this->dispatchSuspensionJob($customer, $reason, $accumulatedDelay);
            if (!empty($customer->phone)) {
                $accumulatedDelay += \App\Services\WhatsAppService::getRandomDelay();
            }
        }
    }

    private function dispatchSuspensionJob($customer, $reason, int $waDelay = 0)
    {
        if ($this->option('dry-run')) {
            $this->line("Dry-run: Would dispatch suspension for {$customer->username}. Reason: {$reason}");
            return;
        }

        ProcessCustomerSuspension::dispatch($customer, $reason, $waDelay);
        $this->warn("Dispatched suspension job for {$customer->username} (WA Delay: {$waDelay}s)");
    }
}
