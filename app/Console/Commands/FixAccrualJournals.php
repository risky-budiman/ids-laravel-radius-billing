<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAccrualJournals extends Command
{
    protected $signature = 'journals:fix-accrual-to-cash';
    protected $description = 'Koreksi jurnal BILL-xxx: pindahkan credit dari Pendapatan (4101) ke Pendapatan Ditangguhkan (2102) untuk invoice yang masih unpaid';

    public function handle()
    {
        $revenueAccount = ChartOfAccount::where('code', '4101')->first();
        $deferredAccount = ChartOfAccount::where('code', '2102')->first();

        if (!$revenueAccount || !$deferredAccount) {
            $this->error('Akun 4101 (Pendapatan Internet) atau 2102 (Pendapatan Ditangguhkan) tidak ditemukan!');
            return 1;
        }

        $this->info("=== Koreksi Jurnal Accrual → Cash Basis ===");
        $this->info("Akun Pendapatan (4101): {$revenueAccount->name} [ID: {$revenueAccount->id}]");
        $this->info("Akun Ditangguhkan (2102): {$deferredAccount->name} [ID: {$deferredAccount->id}]");
        $this->newLine();

        // 1. Find all unpaid invoices
        $unpaidInvoices = Invoice::where('status', 'unpaid')->get();
        $this->info("Invoice unpaid ditemukan: {$unpaidInvoices->count()}");

        if ($unpaidInvoices->isEmpty()) {
            $this->info('Tidak ada invoice unpaid. Tidak perlu koreksi.');
            return 0;
        }

        $corrected = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($unpaidInvoices as $invoice) {
            $reference = 'BILL-' . $invoice->invoice_number;
            $journal = Journal::where('reference', $reference)->first();

            if (!$journal) {
                $this->warn("  ⚠ Jurnal {$reference} tidak ditemukan, skip.");
                $skipped++;
                continue;
            }

            // Find journal items that credit to Pendapatan (4101) in this journal
            $itemsToFix = JournalItem::where('journal_id', $journal->id)
                ->where('account_id', $revenueAccount->id)
                ->where('credit', '>', 0)
                ->get();

            if ($itemsToFix->isEmpty()) {
                $this->line("  ✓ {$reference} — sudah benar (tidak ada credit ke 4101)");
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($itemsToFix, $deferredAccount, $reference) {
                    foreach ($itemsToFix as $item) {
                        $item->update(['account_id' => $deferredAccount->id]);
                    }
                });

                $totalCredit = $itemsToFix->sum('credit');
                $this->info("  ✅ {$reference} — Rp " . number_format($totalCredit, 0, ',', '.') . " dipindah dari 4101 → 2102");
                $corrected++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$reference} — Error: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("=== Hasil Koreksi ===");
        $this->info("Dikoreksi : {$corrected}");
        $this->info("Dilewati  : {$skipped}");
        $this->info("Error     : {$errors}");

        if ($corrected > 0) {
            $this->newLine();
            $this->info("✅ Koreksi selesai. Pendapatan di dashboard sekarang hanya dari invoice yang sudah dibayar.");
        }

        return 0;
    }
}
