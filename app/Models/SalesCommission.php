<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCommission extends Model
{
    protected $fillable = [
        'sales_id',
        'customer_id',
        'invoice_id',
        'base_amount',
        'commission_rate',
        'commission_type',
        'commission_amount',
        'status',
        'withdrawal_id'
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function withdrawal()
    {
        return $this->belongsTo(SalesWithdrawal::class);
    }
}
