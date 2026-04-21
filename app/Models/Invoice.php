<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use LogsActivity;
    protected $fillable = [
        'invoice_number',
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
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
