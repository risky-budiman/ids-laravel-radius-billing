<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Penghapusan piutang macet (write-off).
 *
 * Invoice diakui saat terbit sebagai Piutang Pelanggan (1104) / Pendapatan Ditangguhkan (2102), dan
 * pendapatan baru diakui saat dibayar. Karena pendapatannya belum pernah diakui, write-off cukup
 * membalik jurnal penagihan: Debit 2102 / Kredit 1104 — tanpa beban, laba rugi tidak berubah.
 * Invoice berstatus written_off keluar dari piutang, tagihan portal, pengingat & dasar isolir.
 * Bila pelanggan ternyata membayar, write-off dibatalkan otomatis lalu pembayaran dicatat seperti biasa.
 */
class InvoiceWriteOffService
{
    public function writeOff(Invoice $invoice, string $reason, ?User $by): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason, $by) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== 'unpaid' && $invoice->status !== 'overdue') {
                throw new \DomainException("Invoice {$invoice->invoice_number} tidak berstatus belum dibayar.");
            }
            if (trim($reason) === '') {
                throw new \DomainException('Alasan penghapusan wajib diisi.');
            }

            $invoice->update([
                'status' => 'written_off', 'written_off_at' => now(), 'written_off_by' => $by?->id,
                'write_off_reason' => mb_substr(trim($reason), 0, 500),
            ]);
            $this->journal($invoice, false, $by);

            return $invoice->fresh();
        });
    }

    /** Batalkan write-off (mis. pelanggan akhirnya membayar). Invoice kembali belum dibayar. */
    public function restore(Invoice $invoice, ?User $by): Invoice
    {
        return DB::transaction(function () use ($invoice, $by) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== 'written_off') {
                throw new \DomainException("Invoice {$invoice->invoice_number} tidak sedang dihapus buku.");
            }
            $invoice->update(['status' => 'unpaid', 'written_off_at' => null, 'written_off_by' => null, 'write_off_reason' => null]);
            $this->journal($invoice, true, $by);

            return $invoice->fresh();
        });
    }

    /** Balik jurnal penagihan (hanya bila invoice memang punya jurnal BILL-). */
    private function journal(Invoice $invoice, bool $reverse, ?User $by): void
    {
        if (! Journal::where('reference', 'BILL-' . $invoice->invoice_number)->exists()) {
            return;   // invoice tanpa jurnal penagihan: tidak ada piutang di buku yang perlu dihapus
        }
        if (function_exists('is_accounting_locked') && is_accounting_locked(now())) {
            throw new \DomainException('Periode akuntansi saat ini dikunci; jurnal penghapusan tidak bisa dibuat.');
        }
        $receivable = ChartOfAccount::where('code', '1104')->value('id');
        $deferred = ChartOfAccount::where('code', '2102')->value('id');
        if (! $receivable || ! $deferred) {
            Log::error("Write-off {$invoice->invoice_number}: akun 1104/2102 tidak ditemukan, jurnal tidak dibuat.");

            return;
        }

        $amount = (float) $invoice->amount;
        $journal = Journal::create([
            'date' => now(),
            'reference' => ($reverse ? 'WO-REV-' : 'WO-') . $invoice->invoice_number,
            'description' => ($reverse ? 'Pembatalan penghapusan piutang ' : 'Penghapusan piutang macet ') . $invoice->invoice_number
                . ' - ' . ($invoice->customer?->name ?? '') . (! $reverse && $invoice->write_off_reason ? ' (' . $invoice->write_off_reason . ')' : ''),
            'created_by' => $by?->id ?? auth()->id(),
        ]);
        // Write-off: Debit Pendapatan Ditangguhkan / Kredit Piutang. Pembatalan: sebaliknya.
        JournalItem::create(['journal_id' => $journal->id, 'account_id' => $reverse ? $receivable : $deferred, 'debit' => $amount, 'credit' => 0]);
        JournalItem::create(['journal_id' => $journal->id, 'account_id' => $reverse ? $deferred : $receivable, 'debit' => 0, 'credit' => $amount]);
    }
}
