<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSignalLog extends Model
{
    public $timestamps = false; // We use created_at manually

    protected $fillable = [
        'customer_id',
        'rx_power',
        'status',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'rx_power' => 'float'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
