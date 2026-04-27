<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltPonPort extends Model
{
    use HasFactory;

    protected $fillable = [
        'olt_id',
        'slot',
        'pon_port',
        'status',
        'description',
    ];

    protected $casts = [
        'slot' => 'integer',
        'pon_port' => 'integer',
    ];

    /**
     * Get the OLT that owns the PON port.
     */
    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }
}
