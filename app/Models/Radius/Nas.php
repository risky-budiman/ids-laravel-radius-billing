<?php

namespace App\Models\Radius;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Nas extends Model
{
    use LogsActivity;
    protected $table = 'nas';
    public $timestamps = false;
    
    protected $fillable = [
        'nasname',
        'shortname',
        'type',
        'ports',
        'secret',
        'server',
        'community',
        'description',
    ];
}
