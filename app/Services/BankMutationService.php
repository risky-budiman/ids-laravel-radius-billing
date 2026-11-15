<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankMutation;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mutasi masuk dari bank (webhook Moota) -> pelunasan invoice.
 *
 * Aturan pencocokan (tidak pernah menebak; bila ragu, mutasi menunggu dicocokkan manual):
 *  1. Keterangan transfer memuat nomor invoice yang belum lunas -> invoice itu (nominal harus cukup).
 *  2. Tepat satu invoice belum lunas dengan nominal sama -> invoice itu.
 *  3. Beberapa invoice bernominal sama -> hanya bila keterangan menyebut kode pelanggan atau nama
 *     lengkap (sebagai kata utuh) dari TEPAT satu di antaranya.
 * Setiap mutasi dicatat sekali (mutation_id), jadi kiriman ulang tidak melunasi invoice lain.
 */
class BankMutationService
{
    private const TOLERANCE = 0.5;

    /** Simpan & proses satu mutasi dari payload webhook. Null bila sudah pernah diterima. */
    public function receive(string $provider, array $m): ?BankMutation
    {
        $type = strtoupper((string) ($m['type'] ?? ''));
        $amount = round((float) ($m['amount'] ?? 0), 2);
        $description = trim((string) ($m['description'] ?? $m['note'] ?? ''));
        $mutationId = isset($m['mutation_id']) ? (string) $m['mutation_id'] : null;
        $at = $m['date'] ?? $m['created_at'] ?? null;
        $fingerprint = $provider . ':' . ($mutationId ?: sha1(json_encode([$m['bank_id'] ?? null, $type, $amount, $description, $at])));

        return DB::transaction(function () use ($provider, $m, $type, $amount, $description, $mutationId, $at, $fingerprint) {
            if (BankMutation::where('fingerprint', $fingerprint)->lockForUpdate()->exists()) {
                Log::info("Mutasi {$fingerprint} sudah pernah diterima — dilewati.");

                return null;
            }

            $mutation = BankMutation::create([
                'provider' => $provider, 'fingerprint' => $fingerprint, 'mutation_id' => $mutationId,
                'bank_id' => isset($m['bank_id']) ? (string) $m['bank_id'] : null, 'type' => $type, 'amount' => $amount,
                'description' => $description, 'mutation_at' => $this->parseDate($at), 'raw' => $m,
                'status' => 'unmatched',
            ]);

            if ($type !== 'CR' || $amount <= 0) {
                $mutation->update(['status' => 'ignored', 'note' => 'Bukan dana masuk.']);

                return $mutation;
            }

            [$invoice, $reason] = $this->findInvoice($amount, $description);
            if (! $invoice) {
                $mutation->update(['note' => $reason]);
                Log::warning("Mutasi {$provider} belum cocok: Rp {$amount} — {$reason}");

                return $mutation;
            }

            $this->settle($mutation, $invoice, 'matched', null, $reason);

            return $mutation;
        });
    }

    /**
     * @return array{0: ?Invoice, 1: string}  [invoice, alasan]
     */
    public function findInvoice(float $amount, string $description): array
    {
        $desc = mb_strtoupper($description);

        // 1. Nomor invoice di keterangan
        preg_match_all('/INV[-A-Z0-9]*[0-9][-A-Z0-9]*/', $desc, $m);
        $numbers = array_unique($m[0]);
        if ($numbers) {
            $byNumber = Invoice::with('customer')->where('status', 'unpaid')
                ->whereIn(DB::raw('UPPER(invoice_number)'), $numbers)->get();
            if ($byNumber->count() === 1) {
                $inv = $byNumber->first();
                if ($amount + self::TOLERANCE < (float) $inv->amount) {
                    return [null, "Menyebut {$inv->invoice_number}, tetapi nominal kurang dari tagihan Rp " . number_format((float) $inv->amount, 0, ',', '.') . '.'];
                }

                return [$inv, "Nomor invoice {$inv->invoice_number} di keterangan transfer."];
            }
        }

        // 2-3. Nominal sama
        $candidates = Invoice::with('customer')->where('status', 'unpaid')
            ->whereBetween('amount', [$amount - self::TOLERANCE, $amount + self::TOLERANCE])->get();

        if ($candidates->isEmpty()) {
            return [null, 'Tidak ada invoice belum lunas dengan nominal ini.'];
        }
        if ($candidates->count() === 1) {
            return [$candidates->first(), 'Satu-satunya invoice belum lunas dengan nominal ini.'];
        }

        $named = $candidates->filter(function (Invoice $inv) use ($desc) {
            $c = $inv->customer;

            return $c && ($this->hasWord($desc, $c->customer_code) || $this->hasWord($desc, $c->name, 4));
        });
        if ($named->count() === 1) {
            return [$named->first(), 'Nominal sama dengan ' . $candidates->count() . ' invoice; keterangan menyebut pelanggan ' . $named->first()->customer->name . '.'];
        }

        return [null, $candidates->count() . ' invoice belum lunas bernominal sama'
            . ($named->count() > 1 ? ' dan keterangan cocok dengan ' . $named->count() . ' pelanggan' : ' dan keterangan tidak menyebut pelanggan')
            . ' — perlu dicocokkan manual.'];
    }

    /** Cocokkan manual: lunasi invoice (nomor) dengan mutasi ini. */
    public function matchManually(BankMutation $mutation, string $invoiceNumber, ?User $by): Invoice
    {
        return DB::transaction(function () use ($mutation, $invoiceNumber, $by) {
            $mutation = BankMutation::whereKey($mutation->id)->lockForUpdate()->firstOrFail();
            if (! in_array($mutation->status, ['unmatched', 'ignored'], true)) {
                throw new \DomainException('Mutasi ini sudah dipakai untuk melunasi invoice.');
            }
            $invoice = Invoice::where('invoice_number', trim($invoiceNumber))->first();
            if (! $invoice) {
                throw new \DomainException("Invoice {$invoiceNumber} tidak ditemukan.");
            }
            if ($invoice->status === 'paid') {
                throw new \DomainException("Invoice {$invoice->invoice_number} sudah lunas.");
            }
            if ((float) $mutation->amount + self::TOLERANCE < (float) $invoice->amount) {
                throw new \DomainException('Nominal mutasi (Rp ' . number_format((float) $mutation->amount, 0, ',', '.')
                    . ') kurang dari tagihan (Rp ' . number_format((float) $invoice->amount, 0, ',', '.') . ').');
            }

            $this->settle($mutation, $invoice, 'manual', $by, 'Dicocokkan manual oleh ' . ($by?->name ?? 'sistem') . '.');

            return $invoice->fresh();
        });
    }

    public function ignore(BankMutation $mutation, ?User $by, ?string $note): void
    {
        if ($mutation->status !== 'unmatched') {
            throw new \DomainException('Hanya mutasi yang belum cocok yang bisa diabaikan.');
        }
        $mutation->update(['status' => 'ignored', 'handled_by' => $by?->id, 'note' => $note ?: 'Diabaikan (bukan pembayaran invoice).']);
    }

    private function settle(BankMutation $mutation, Invoice $invoice, string $status, ?User $by, string $reason): void
    {
        $account = BankAccount::firstOrCreate(['bank_name' => strtoupper($mutation->provider)],
            ['account_name' => 'Gateway ' . strtoupper($mutation->provider), 'type' => 'payment_gateway', 'is_active' => true]);

        app(InvoiceService::class)->markAsPaid($invoice, $account->id);
        $mutation->update(['status' => $status, 'invoice_id' => $invoice->id, 'note' => $reason, 'handled_by' => $by?->id]);
        Log::info("Mutasi {$mutation->fingerprint} melunasi {$invoice->invoice_number}: {$reason}");
    }

    private function hasWord(string $haystackUpper, ?string $needle, int $minLength = 3): bool
    {
        $needle = trim(preg_replace('/\s+/', ' ', (string) $needle));
        if (mb_strlen($needle) < $minLength) {
            return false;
        }
        $pattern = '/(?<![\p{L}\p{N}])' . str_replace('\ ', '\s+', preg_quote(mb_strtoupper($needle), '/')) . '(?![\p{L}\p{N}])/u';

        return (bool) preg_match($pattern, $haystackUpper);
    }

    private function parseDate($value): ?\Carbon\Carbon
    {
        try {
            return $value ? \Carbon\Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
