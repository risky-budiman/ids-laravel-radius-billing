<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    protected $accountingService;
    protected $partnerService;
    protected $salesService;

    public function __construct(AccountingService $accountingService, PartnerService $partnerService, SalesCommissionService $salesService)
    {
        $this->accountingService = $accountingService;
        $this->partnerService = $partnerService;
        $this->salesService = $salesService;
    }

    /**
     * Mark an invoice as paid and trigger all related side effects
     */
    public function markAsPaid(Invoice $invoice, $bankAccountId = null)
    {
        return DB::transaction(function() use ($invoice, $bankAccountId) {
            if ($invoice->status === 'paid') return $invoice;

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Handle Booster Activation on payment
            if (str_starts_with($invoice->invoice_number, 'INV-BOOST-')) {
                try {
                    $purchaseId = (int) str_replace('INV-BOOST-', '', $invoice->invoice_number);
                    $purchase = \App\Models\CustomerBooster::find($purchaseId);
                    if ($purchase && $purchase->payment_status !== 'paid') {
                        $purchase->update([
                            'payment_status' => 'paid',
                            'paid_at' => now(),
                        ]);

                        // Reset RADIUS usage for active sessions
                        \App\Models\Radius\RadAcct::where('username', $invoice->customer->username)
                            ->whereNull('acctstoptime')
                            ->update([
                                'acctinputoctets' => 0,
                                'acctoutputoctets' => 0
                            ]);

                        // Send CoA Disconnect
                        $nas = \App\Models\Radius\Nas::first();
                        if ($nas) {
                            $coa = new \App\Services\RadiusCoAService();
                            $coa->disconnect($nas->nasname, $nas->secret, $invoice->customer->username);
                        }
                        Log::info("Booster purchase {$purchaseId} activated via payment webhook for {$invoice->customer->username}");
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to activate booster for invoice {$invoice->invoice_number}: " . $e->getMessage());
                }
            }

            // 1. Record to Treasury
            if (!$bankAccountId) {
                $cashAccount = BankAccount::firstOrCreate(
                    ['bank_name' => 'KAS TUNAI'],
                    ['account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'is_active' => true]
                );
                $bankAccountId = $cashAccount->id;
            }

            $bankTx = new BankTransaction([
                'bank_account_id' => $bankAccountId,
                'type' => 'deposit',
                'amount' => $invoice->amount,
                'description' => '[Pembayaran Invoice] ' . $invoice->invoice_number . ' - Pelanggan: ' . $invoice->customer->name,
                'transaction_date' => now(),
                'created_by' => auth()->id() ?? 1,
            ]);
            $bankTx->skipAutoJournal = true;
            $bankTx->save();

            // 2. Reactivate Customer
            $customer = $invoice->customer;
            if ($customer) {
                $customer->update([
                    'is_active' => true,
                    'status' => Customer::STATUS_ACTIVE
                ]);

                if ($customer->billing_method === 'renewal') {
                    $customer->syncBillingDates();
                }

                // Handle Installation specific marking if using special numbering
                if (str_contains($invoice->invoice_number, 'INV-INST')) {
                    $customer->update([
                        'installation_paid_at' => now(),
                        'installation_bank_account_id' => $bankAccountId,
                        'activation_grace_expires_at' => null,
                    ]);
                }
            }

            // 3. Auto-Journal: Invoice Payment
            try {
                $bankAccount = BankAccount::find($bankAccountId);
                $this->accountingService->recordInvoicePayment($invoice, $bankAccount);
            } catch (\Exception $e) {
                Log::error("Auto-journal failed for PAY-{$invoice->invoice_number}: " . $e->getMessage());
            }

            // 4. Partner Commission
            try {
                $this->partnerService->processPaymentCommission($invoice);
            } catch (\Exception $e) {
                Log::error("Partner commission processing failed for {$invoice->invoice_number}: " . $e->getMessage());
            }

            // 5. Sales Commission (Internal)
            try {
                $this->salesService->processPaymentCommission($invoice);
            } catch (\Exception $e) {
                Log::error("Sales commission processing failed for {$invoice->invoice_number}: " . $e->getMessage());
            }

            return $invoice;
        });
    }
}
