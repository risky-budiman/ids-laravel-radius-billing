<?php

namespace App\Models\Radius;

use Illuminate\Database\Eloquent\Model;

class RadUserGroup extends Model
{
    protected $table = 'radusergroup';
    public $timestamps = false;
    
    // table doesn't have an id primary key
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'username',
        'groupname',
        'priority'
    ];
}
