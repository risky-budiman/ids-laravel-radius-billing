<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'target_phone',
        'message',
        'status',
        'error_reason',
    ];
}
