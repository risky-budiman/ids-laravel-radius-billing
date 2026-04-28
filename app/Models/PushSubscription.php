<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'subscribable_id',
        'subscribable_type',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    /**
     * Get the parent subscribable model (Customer or User).
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }
}
