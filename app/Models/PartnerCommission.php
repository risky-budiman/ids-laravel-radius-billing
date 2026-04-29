<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerCommission extends Model
{
    protected $fillable = [
        'partner_id',
        'customer_id',
        'invoice_id',
        'amount',
        'base_amount',
        'rate',
        'type',
        'status',
        'withdrawal_id',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
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
        return $this->belongsTo(PartnerWithdrawal::class);
    }
}
