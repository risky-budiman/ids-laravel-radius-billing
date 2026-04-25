<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcsServer extends Model
{
    protected $fillable = [
        'name',
        'url',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
