<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'asset_code',
        'purchase_date',
        'purchase_price',
        'salvage_value',
        'useful_life_months',
        'accumulated_depreciation',
        'status',
        'asset_account_id',
        'depreciation_account_id',
        'accumulated_account_id',
        'description',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];

    public function getNetBookValueAttribute()
    {
        return $this->purchase_price - $this->accumulated_depreciation;
    }

    public function assetAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'asset_account_id');
    }

    public function depreciationAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_account_id');
    }

    public function accumulatedAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'accumulated_account_id');
    }
}
