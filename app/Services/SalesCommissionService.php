<?php

namespace App\Services;

use App\Models\SalesCommission;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class SalesCommissionService
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Calculate and record commission for an invoice payment (Sales Internal)
     */
    public function processPaymentCommission(Invoice $invoice)
    {
        // 1. Cek apakah modul sales aktif
        if (get_setting('enable_sales_commission_module') != '1') {
            return null;
        }

        $customer = $invoice->customer;
        // Hanya proses jika pelanggan memiliki Sales yang terdaftar
        if (!$customer || !$customer->sales_id) {
            return null;
        }

        $sales = $customer->sales;
        if (!$sales) {
            return null;
        }

        // 2. Tentukan Rate dan Type (Hierarki: Customer -> Global)
        $rate = $customer->sales_commission_rate;
        $type = $customer->sales_commission_type;

        if (is_null($rate)) {
            $rate = get_setting('default_sales_commission_rate', 0);
            $type = get_setting('default_sales_commission_type', 'percentage');
        }

        // 3. Hitung Nominal Komisi
        $commissionAmount = 0;
        $baseAmount = $invoice->amount; 

        if ($type === 'percentage') {
            $commissionAmount = ($rate / 100) * $baseAmount;
        } else {
            $commissionAmount = $rate;
        }

        if ($commissionAmount <= 0) {
            return null;
        }

        try {
            // 4. Simpan Record Komisi (Detailed Ledger)
            $commission = SalesCommission::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'base_amount' => $baseAmount,
                'commission_rate' => $rate,
                'commission_type' => $type,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
            ]);

            // 5. Catat ke Jurnal Akuntansi (Beban vs Hutang)
            $this->accountingService->recordSalesCommission($commission);

            return $commission;
        } catch (\Exception $e) {
            Log::error("Failed to process sales commission: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get current balance for a sales staff
     */
    public function getSalesBalance($salesId)
    {
        $totalEarned = SalesCommission::where('sales_id', $salesId)->sum('commission_amount');
        $totalWithdrawn = \App\Models\SalesWithdrawal::where('sales_id', $salesId)
            ->where('status', 'paid')
            ->sum('amount');

        return $totalEarned - $totalWithdrawn;
    }

    /**
     * Record a manual withdrawal for sales staff
     */
    public function recordWithdrawal($salesId, $amount, $bankAccountId, $reference = null, $notes = null)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($salesId, $amount, $bankAccountId, $reference, $notes) {
            $withdrawal = \App\Models\SalesWithdrawal::create([
                'sales_id' => $salesId,
                'amount' => $amount,
                'bank_account_id' => $bankAccountId,
                'reference_number' => $reference,
                'admin_notes' => $notes,
                'status' => 'paid',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Record to Accounting Journal
            $bankAccount = \App\Models\BankAccount::find($bankAccountId);
            $this->accountingService->recordSalesWithdrawal($withdrawal, $bankAccount);

            return $withdrawal;
        });
    }
}
