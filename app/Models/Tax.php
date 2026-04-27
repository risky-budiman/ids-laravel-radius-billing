<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = [
        'name',
        'code',
        'rate',
        'description',
        'is_active',
        'chart_of_account_id',
    ];

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
