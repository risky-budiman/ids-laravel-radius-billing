<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odc extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'stb_id',
        'location_description',
        'latitude',
        'longitude',
        'total_ports'
    ];

    public function stb()
    {
        return $this->belongsTo(Stb::class);
    }

    public function odps()
    {
        return $this->hasMany(Odp::class);
    }
}
