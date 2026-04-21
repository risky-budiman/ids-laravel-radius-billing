<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'customer_code',
        'region_code',
        'sto_code',
        'stb_code',
        'username',
        'name',
        'email',
        'phone',
        'address',
        'package_id',
        'is_active',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $region = str_pad($customer->region_code ?? '000', 3, '0', STR_PAD_LEFT);
                $sto = str_pad($customer->sto_code ?? '000', 3, '0', STR_PAD_LEFT);
                $stb = str_pad($customer->stb_code ?? '000', 3, '0', STR_PAD_LEFT);
                $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $customer->customer_code = $region . $sto . $stb . $random;
            }
        });
    }
}
