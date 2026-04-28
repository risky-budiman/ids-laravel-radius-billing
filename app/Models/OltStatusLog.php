<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OltStatusLog extends Model
{
    protected $fillable = [
        'olt_id',
        'is_online',
        'cpu_usage',
        'memory_usage',
        'last_polled_at'
    ];

    protected $casts = [
        'last_polled_at' => 'datetime',
        'cpu_usage' => 'array',
        'memory_usage' => 'array',
        'is_online' => 'boolean'
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }
}
