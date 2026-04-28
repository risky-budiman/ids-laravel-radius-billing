<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBooster extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'booster_id',
        'amount_paid',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booster()
    {
        return $this->belongsTo(Booster::class);
    }
}
