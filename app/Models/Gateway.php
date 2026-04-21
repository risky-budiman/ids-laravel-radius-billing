<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = ['type', 'provider', 'credentials', 'is_active'];

    protected $casts = [
        'credentials' => 'array',
        'is_active' => 'boolean',
    ];
}
