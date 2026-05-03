<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'year',
        'closed_at',
        'closed_by',
        'net_profit',
        'total_assets',
        'total_liabilities',
        'total_equity',
        'is_closed',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'is_closed' => 'boolean',
        'net_profit' => 'decimal:2',
        'total_assets' => 'decimal:2',
        'total_liabilities' => 'decimal:2',
        'total_equity' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getMonthNameAttribute()
    {
        return \Carbon\Carbon::create()->month($this->month)->translatedFormat('F');
    }

    public function getPeriodStringAttribute()
    {
        return $this->month_name . ' ' . $this->year;
    }
}
