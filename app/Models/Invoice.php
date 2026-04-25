<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use LogsActivity;
    protected $fillable = [
        'invoice_number',
        'billing_period',
        'period_start',
        'period_end',
        'customer_id',
        'amount',
        'tax',
        'total',
        'status',
        'due_date',
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
