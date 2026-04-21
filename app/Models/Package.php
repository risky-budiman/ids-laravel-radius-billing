<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'type',
        'price',
        'download_speed',
        'upload_speed',
        'description',
        'is_active',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
