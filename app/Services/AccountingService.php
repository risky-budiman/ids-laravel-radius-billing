<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Record a journal entry for an invoice generation (Accrual)
     * Debit: Piutang Pelanggan (1103)
     * Credit: Pendapatan Internet (4101)
     */
    public function recordInvoiceGenerated($invoice)
    {
        return DB::transaction(function () use ($invoice) {
            $debitAccountId = ChartOfAccount::where('code', '1104')->first()->id; // Piutang Pelanggan (New Code)
            $creditAccountId = ChartOfAccount::where('code', '4101')->first()->id; // Pendapatan Internet
            $taxAccountId = $invoice->tax?->chart_of_account_id ?? ChartOfAccount::where('code', '2103')->first()?->id;

            $date = $invoice->created_at ?? now();
            if (is_accounting_locked($date)) {
                \Illuminate\Support\Facades\Log::warning("Attempted to record invoice journal in locked period: " . $invoice->invoice_number);
                return null;
            }

            $journal = Journal::create([
                'date' => $date,
                'reference' => 'BILL-' . $invoice->invoice_number,
                'description' => 'Penagihan Invoice ' . $invoice->invoice_number . ' - ' . $invoice->customer->name,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit: Piutang Pelanggan (Total Amount)
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $invoice->amount,
                'credit' => 0,
            ]);

            // Credit: Pendapatan Internet (Subtotal / Amount before tax)
            $subtotal = $invoice->subtotal > 0 ? $invoice->subtotal : ($invoice->amount - $invoice->tax_amount);
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $subtotal,
            ]);

            // Credit: Hutang Pajak (Tax Amount)
            if ($invoice->tax_amount > 0 && $taxAccountId) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $taxAccountId,
                    'debit' => 0,
                    'credit' => $invoice->tax_amount,
                ]);
            }

            return $journal;
        });
    }

    /**
     * Record a journal entry for an invoice payment
     * Debit: Bank (1102) / Linked Account
     * Credit: Piutang Pelanggan (1103)
     */
    public function recordInvoicePayment($invoice, $bankAccount)
    {
        return DB::transaction(function () use ($invoice, $bankAccount) {
            // 1. Determine Accounts
            // Debit: The Bank Account's CoA
            $debitAccountId = $bankAccount->chart_of_account_id ?? ChartOfAccount::where('code', '1102')->first()->id;

            // Credit: Piutang Pelanggan (1104)
            $creditAccountId = ChartOfAccount::where('code', '1104')->first()->id;

            $date = now();
            if (is_accounting_locked($date)) {
                \Illuminate\Support\Facades\Log::warning("Attempted to record payment journal in locked period: " . $invoice->invoice_number);
                return null;
            }

            // 2. Create Journal Header
            $journal = Journal::create([
                'date' => now(),
                'reference' => 'PAY-' . $invoice->invoice_number,
                'description' => 'Pembayaran Invoice ' . $invoice->invoice_number . ' - ' . $invoice->customer->name,
                'created_by' => auth()->id() ?? 1,
            ]);

            // 3. Create Journal Items
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $invoice->amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $invoice->amount,
            ]);

            return $journal;
        });
    }

    /**
     * Record a journal entry for installation fee payment
     */
    public function recordInstallationPayment($customer, $amount, $bankAccount)
    {
        return DB::transaction(function () use ($customer, $amount, $bankAccount) {
            $debitAccountId = $bankAccount->chart_of_account_id ?? ChartOfAccount::where('code', '1102')->first()->id;
            $creditAccountId = ChartOfAccount::where('code', '4102')->first()->id; // Pendapatan Instalasi
            
            $date = now();
            if (is_accounting_locked($date)) {
                \Illuminate\Support\Facades\Log::warning("Attempted to record installation journal in locked period: " . $customer->customer_code);
                return null;
            }

            $journal = Journal::create([
                'date' => now(),
                'reference' => 'INST-' . $customer->customer_code,
                'description' => 'Pembayaran Biaya Instalasi - ' . $customer->name,
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
            ]);

            return $journal;
        });
    }

    /**
     * Record a general bank transaction (income/expense)
     */
    public function recordBankTransaction($transaction)
    {
        if (is_accounting_locked($transaction->transaction_date)) {
            \Illuminate\Support\Facades\Log::warning("Attempted to record bank transaction journal in locked period: TRX-" . $transaction->id);
            return null;
        }

        return DB::transaction(function () use ($transaction) {
            $bankAccount = $transaction->bankAccount;
            $coaBank = $bankAccount->chart_of_account_id 
                ? ChartOfAccount::find($bankAccount->chart_of_account_id)
                : ChartOfAccount::where('code', '1102')->first();
            
            if (!$coaBank) {
                \Illuminate\Support\Facades\Log::error("Missing CoA for Bank Account (1102) during TRX-" . $transaction->id);
                return null;
            }
            
            $coaBankId = $coaBank->id;
            
            $isIncome = $transaction->type === 'income' || $transaction->type === 'deposit';
            
            // Determine Offset Account (Category)
            $offsetAccountId = $transaction->chart_of_account_id;
            if (!$offsetAccountId) {
                $code = $isIncome ? '4103' : '5106'; // Default Income / Expense
                $offsetAccount = ChartOfAccount::where('code', $code)->first();
                if (!$offsetAccount) {
                    \Illuminate\Support\Facades\Log::error("Missing default CoA ($code) during TRX-" . $transaction->id);
                    return null;
                }
                $offsetAccountId = $offsetAccount->id;
            }

            $journal = Journal::create([
                'date' => $transaction->transaction_date,
                'reference' => 'TRX-' . $transaction->id,
                'description' => $transaction->description,
                'created_by' => $transaction->created_by ?? 1,
            ]);

            if ($isIncome) {
                $debitAccountId = $coaBankId;
                $creditAccountId = $offsetAccountId;
            } else {
                $debitAccountId = $offsetAccountId;
                $creditAccountId = $coaBankId;
            }

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $transaction->amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $transaction->amount,
            ]);

            return $journal;
        });
    }
    /**
     * Record a journal entry for inventory stock-in (Purchase)
     * Debit: Persediaan Barang (1104)
     * Debit: PPN Masukan (1105) - if any
     * Credit: Kas Utama (1101)
     */
    public function recordInventoryPurchase($movement)
    {
        if ($movement->type !== 'in') return null;

        if (is_accounting_locked($movement->created_at)) {
            \Illuminate\Support\Facades\Log::warning("Attempted to record inventory purchase in locked period: MOV-" . $movement->id);
            return null;
        }

        return DB::transaction(function () use ($movement) {
            $inventoryAccountId = ChartOfAccount::where('code', '1105')->first()?->id; // Persediaan Barang
            $taxInputAccountId = ChartOfAccount::where('code', '1106')->first()?->id; // PPN Masukan
            $creditAccountId = ChartOfAccount::where('code', '1101')->first()?->id; // Kas Tunai

            if (!$inventoryAccountId || !$creditAccountId) {
                \Illuminate\Support\Facades\Log::error("Missing CoA for Inventory Purchase (1104/1101)");
                return null;
            }

            if ($movement->total_amount <= 0) {
                return null;
            }

            $journal = Journal::create([
                'date' => $movement->created_at,
                'reference' => 'STOCK-IN-' . ($movement->reference ?? $movement->id),
                'description' => 'Pembelian/Masuk Barang: ' . $movement->item->name . ' (' . $movement->quantity . ' ' . $movement->item->unit . ')',
                'created_by' => $movement->user_id ?? auth()->id() ?? 1,
            ]);

            // Debit: Persediaan Barang (Subtotal)
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryAccountId,
                'debit' => $movement->subtotal,
                'credit' => 0,
            ]);

            // Debit: PPN Masukan (Tax Amount)
            if ($movement->tax_amount > 0 && $taxInputAccountId) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $taxInputAccountId,
                    'debit' => $movement->tax_amount,
                    'credit' => 0,
                ]);
            }

            // Credit: Kas/Hutang (Total Amount)
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $movement->total_amount,
            ]);

            return $journal;
        });
    }

    /**
     * Record a journal entry for partner commission earn
     * Debit: Beban Komisi Mitra (5110)
     * Credit: Hutang Komisi Mitra (2104)
     */
    public function recordPartnerCommission($commission)
    {
        return DB::transaction(function () use ($commission) {
            $debitAccountId = ChartOfAccount::where('code', '5110')->first()?->id;
            $creditAccountId = ChartOfAccount::where('code', '2104')->first()?->id;

            if (!$debitAccountId || !$creditAccountId) {
                \Illuminate\Support\Facades\Log::error("Missing CoA for Partner Commission (5110/2104)");
                return null;
            }

            $date = $commission->created_at ?? now();
            if (is_accounting_locked($date)) {
                return null;
            }

            $journal = Journal::create([
                'date' => $date,
                'reference' => 'COMM-' . $commission->id,
                'description' => 'Komisi Mitra: ' . $commission->partner->name . ' - ' . $commission->customer->name . ' (Inv: ' . $commission->invoice->invoice_number . ')',
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $commission->amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $commission->amount,
            ]);

            return $journal;
        });
    }

    /**
     * Record a journal entry for partner withdrawal payment
     * Debit: Hutang Komisi Mitra (2104)
     * Credit: Bank/Kas Account Linked to the withdrawal
     */
    public function recordPartnerWithdrawal($withdrawal, $bankAccount)
    {
        return DB::transaction(function () use ($withdrawal, $bankAccount) {
            $debitAccountId = ChartOfAccount::where('code', '2104')->first()?->id;
            $creditAccountId = $bankAccount->chart_of_account_id ?? ChartOfAccount::where('code', '1102')->first()->id;

            if (!$debitAccountId || !$creditAccountId) {
                \Illuminate\Support\Facades\Log::error("Missing CoA for Partner Withdrawal (2104/Bank)");
                return null;
            }

            $date = $withdrawal->payment_date ?? now();
            if (is_accounting_locked($date)) {
                return null;
            }

            $journal = Journal::create([
                'date' => $date,
                'reference' => 'WD-' . $withdrawal->id,
                'description' => 'Pencairan Komisi Mitra: ' . $withdrawal->partner->name . ' - Reff: ' . ($withdrawal->notes ?? $withdrawal->id),
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $withdrawal->amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $withdrawal->amount,
            ]);

            return $journal;
        });
    }

    /**
     * Record a journal entry for sales commission earn
     * Debit: Beban Insentif Sales (5111)
     * Credit: Hutang Insentif Sales (2105)
     */
    public function recordSalesCommission($commission)
    {
        return DB::transaction(function () use ($commission) {
            $debitAccountId = ChartOfAccount::where('code', '5111')->first()?->id;
            $creditAccountId = ChartOfAccount::where('code', '2105')->first()?->id;

            if (!$debitAccountId || !$creditAccountId) {
                \Illuminate\Support\Facades\Log::error("Missing CoA for Sales Incentive (5111/2105)");
                return null;
            }

            $date = $commission->created_at ?? now();
            if (is_accounting_locked($date)) {
                return null;
            }

            $journal = Journal::create([
                'date' => $date,
                'reference' => 'SALE-COMM-' . $commission->id,
                'description' => 'Insentif Sales: ' . $commission->sales->name . ' - ' . $commission->customer->name . ' (Inv: ' . $commission->invoice->invoice_number . ')',
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $commission->commission_amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $commission->commission_amount,
            ]);

            return $journal;
        });
    }

    /**
     * Record a journal entry for sales withdrawal payment
     * Debit: Hutang Insentif Sales (2105)
     * Credit: Bank/Kas Account
     */
    public function recordSalesWithdrawal($withdrawal, $bankAccount)
    {
        return DB::transaction(function () use ($withdrawal, $bankAccount) {
            $debitAccountId = ChartOfAccount::where('code', '2105')->first()?->id;
            $creditAccountId = $bankAccount->chart_of_account_id ?? ChartOfAccount::where('code', '1102')->first()->id;

            if (!$debitAccountId || !$creditAccountId) {
                \Illuminate\Support\Facades\Log::error("Missing CoA for Sales Withdrawal (2105/Bank)");
                return null;
            }

            $date = $withdrawal->processed_at ?? now();
            if (is_accounting_locked($date)) {
                return null;
            }

            $journal = Journal::create([
                'date' => $date,
                'reference' => 'SALE-WD-' . $withdrawal->id,
                'description' => 'Pencairan Insentif Sales: ' . $withdrawal->sales->name . ' - Reff: ' . ($withdrawal->reference_number ?? $withdrawal->id),
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $withdrawal->amount,
                'credit' => 0,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $withdrawal->amount,
            ]);

            return $journal;
        });
    }

    /**
     * Get a financial snapshot for a specific month/year
     */
    public function getFinancialSnapshot($month, $year)
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // 1. Calculate Net Profit for the period
        $income = JournalItem::whereHas('journal', function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        })->whereHas('account', fn($q) => $q->where('type', 'income'))->get();
        
        $totalIncome = $income->sum('credit') - $income->sum('debit');

        $expense = JournalItem::whereHas('journal', function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        })->whereHas('account', fn($q) => $q->where('type', 'expense'))->get();
        
        $totalExpense = $expense->sum('debit') - $expense->sum('credit');
        $netProfit = $totalIncome - $totalExpense;

        // 2. Calculate Assets, Liabilities, Equity up to the end of period
        $accounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])->get();
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $account) {
            $balance = JournalItem::whereHas('journal', function($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
            })->where('account_id', $account->id)->get();

            if ($account->type === 'asset') {
                $totalAssets += ($balance->sum('debit') - $balance->sum('credit'));
            } elseif ($account->type === 'liability') {
                $totalLiabilities += ($balance->sum('credit') - $balance->sum('debit'));
            } elseif ($account->type === 'equity') {
                $totalEquity += ($balance->sum('credit') - $balance->sum('debit'));
            }
        }

        return [
            'net_profit' => $netProfit,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
        ];
    }
}
