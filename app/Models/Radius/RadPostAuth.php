<?php

namespace App\Models\Radius;

use Illuminate\Database\Eloquent\Model;

class RadPostAuth extends Model
{
    protected $table = 'radpostauth';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'pass',
        'reply',
        'authdate',
    ];

    protected $casts = [
        'authdate' => 'datetime',
    ];

    /**
     * Check if this was a successful authentication.
     */
    public function isSuccess(): bool
    {
        return $this->reply === 'Access-Accept';
    }

    /**
     * Check if this was a failed authentication.
     */
    public function isFailure(): bool
    {
        return $this->reply === 'Access-Reject';
    }
}
