<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stb extends Model
{
    protected $fillable = ['sto_id', 'code', 'name'];

    public function sto()
    {
        return $this->belongsTo(Sto::class);
    }

    protected static function booted()
    {
        static::creating(function ($stb) {
            $nextId = (static::max('id') ?? 0) + 1;
            $stb->code = str_pad($nextId, 3, '0', STR_PAD_LEFT);
        });
    }
}
