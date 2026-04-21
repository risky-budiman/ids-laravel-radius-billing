<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use LogsActivity;
    protected $fillable = ['code', 'name'];

    public function stos()
    {
        return $this->hasMany(Sto::class);
    }

    protected static function booted()
    {
        static::creating(function ($region) {
            $nextId = (static::max('id') ?? 0) + 1;
            $region->code = str_pad($nextId, 3, '0', STR_PAD_LEFT);
        });
    }
}
