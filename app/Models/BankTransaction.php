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

    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->updateBalance();
            
            // Auto-Journal for Bank Transaction
            try {
                (new \App\Services\AccountingService())->recordBankTransaction($transaction);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Auto-journal failed for TRX-{$transaction->id}: " . $e->getMessage());
            }
        });

        static::deleted(function ($transaction) {
            $transaction->updateBalance(true);
            
            // Reverse/Delete Journal when transaction is deleted
            \App\Models\Journal::where('reference', 'TRX-' . $transaction->id)->delete();
        });
    }

    public function updateBalance($isDelete = false)
    {
        $account = $this->bankAccount;
        $amount = (string) $this->amount;
        $currentBalance = (string) $account->balance;

        if ($this->type === 'deposit') {
            $newBalance = $isDelete ? bcsub($currentBalance, $amount, 2) : bcadd($currentBalance, $amount, 2);
        } else {
            $newBalance = $isDelete ? bcadd($currentBalance, $amount, 2) : bcsub($currentBalance, $amount, 2);
        }

        $account->update(['balance' => $newBalance]);
    }
}
