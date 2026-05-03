<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Sto extends Model
{
    use LogsActivity;
    protected $fillable = ['region_id', 'acs_server_id', 'code', 'name', 'latitude', 'longitude'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function acsServer()
    {
        return $this->belongsTo(AcsServer::class);
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
