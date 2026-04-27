<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odp extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'odc_id',
        'latitude',
        'longitude',
        'total_ports'
    ];

    public function odc()
    {
        return $this->belongsTo(Odc::class);
    }
}
