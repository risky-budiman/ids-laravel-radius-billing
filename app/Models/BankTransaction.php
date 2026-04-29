<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

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
        $account = $this->bankAccount;
        $amount = (float) $this->amount;
        $currentBalance = (float) $account->balance;

        if ($this->type === 'deposit') {
            if (function_exists('bcadd')) {
                $newBalance = $isDelete ? bcsub($currentBalance, $amount, 2) : bcadd($currentBalance, $amount, 2);
            } else {
                $newBalance = $isDelete ? ($currentBalance - $amount) : ($currentBalance + $amount);
            }
        } else {
            if (function_exists('bcadd')) {
                $newBalance = $isDelete ? bcadd($currentBalance, $amount, 2) : bcsub($currentBalance, $amount, 2);
            } else {
                $newBalance = $isDelete ? ($currentBalance + $amount) : ($currentBalance - $amount);
            }
        }

        $account->update(['balance' => round($newBalance, 2)]);
    }
}
