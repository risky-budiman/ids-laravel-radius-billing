<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesWithdrawal extends Model
{
    protected $fillable = [
        'sales_id',
        'amount',
        'status',
        'bank_account_id',
        'payment_method',
        'reference_number',
        'admin_notes',
        'processed_at',
        'processed_by'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function commissions()
    {
        return $this->hasMany(SalesCommission::class, 'withdrawal_id');
    }
}
