<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'bank_account_id',
        'chart_of_account_id',
        'type',
        'amount',
        'reference_number',
        'description',
        'transaction_date',
        'related_transaction_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function relatedTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'related_transaction_id');
    }

    public function category()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public $skipAutoJournal = false;

    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->updateBalance();
            
            // Auto-Journal for Bank Transaction
            if (!$transaction->skipAutoJournal) {
                try {
                    (new \App\Services\AccountingService())->recordBankTransaction($transaction);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Auto-journal failed for TRX-{$transaction->id}: " . $e->getMessage());
                }
            }
        });

        static::deleted(function ($transaction) {
            $transaction->updateBalance(true);
            
            // Delete associated Journal
            // 1. Auto-journal (TRX-ID)
            \App\Models\Journal::where('reference', 'TRX-' . $transaction->id)->delete();
            
            // 2. Manual Journal if linked via reference (e.g. JV-xxx)
            if ($transaction->reference_number && strpos($transaction->reference_number, 'JV-') === 0) {
                \App\Models\Journal::where('reference', $transaction->reference_number)->delete();
            }
        });
    }

    public function updateBalance($isDelete = false)
    {
        // Ubah saldo secara atomik di database (balance = balance ± amount). Sebelumnya saldo
        // dibaca lalu ditulis ulang dari PHP, sehingga dua transaksi bersamaan (mis. webhook
        // pembayaran + input kasir) bisa saling menimpa dan saldo tidak lagi sama dengan mutasi.
        $sign = ($this->type === 'deposit') === ! $isDelete ? 1 : -1;
        $delta = round($sign * (float) $this->amount, 2);

        BankAccount::whereKey($this->bank_account_id)->increment('balance', $delta);

        // Objek yang sudah dimuat ikut menampilkan saldo terbaru.
        if ($this->relationLoaded('bankAccount') && $this->bankAccount) {
            $this->bankAccount->refresh();
        }
    }

    public function activityIdentifier(): string
    {
        return trim(($this->type === 'deposit' ? 'Masuk ' : 'Keluar ') . number_format((float) $this->amount, 0, ',', '.')
            . ' · ' . ($this->bankAccount?->account_name ?? 'rekening #' . $this->bank_account_id)
            . ($this->description ? ' · ' . \Illuminate\Support\Str::limit($this->description, 60) : ''));
    }
}
