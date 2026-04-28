<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSignalCache extends Model
{
    protected $fillable = [
        'customer_id',
        'onu_index',
        'rx_power',
        'tx_power',
        'temp',
        'status',
        'last_polled_at',
        'last_alerted_at'
    ];

    protected $casts = [
        'last_polled_at' => 'datetime',
        'last_alerted_at' => 'datetime',
        'rx_power' => 'float'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
