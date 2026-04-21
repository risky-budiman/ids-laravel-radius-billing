<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sto extends Model
{
    protected $fillable = ['region_id', 'code', 'name'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function stbs()
    {
        return $this->hasMany(Stb::class);
    }

    protected static function booted()
    {
        static::creating(function ($sto) {
            $nextId = (static::max('id') ?? 0) + 1;
            $sto->code = str_pad($nextId, 3, '0', STR_PAD_LEFT);
        });
    }
}
