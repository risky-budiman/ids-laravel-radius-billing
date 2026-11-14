<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerIsolation extends Model
{
    protected $fillable = [
        'customer_id', 'username', 'mode', 'ip_address',
        'previous_groups', 'previous_replies', 'isolated_at',
    ];

    protected $casts = [
        'previous_groups' => 'array',
        'previous_replies' => 'array',
        'isolated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
