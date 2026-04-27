<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
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

    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->updateBalance();
        });

        static::deleted(function ($transaction) {
            $transaction->updateBalance(true);
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
