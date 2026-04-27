<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = ['code', 'name', 'type', 'parent_id', 'is_active'];

    protected $appends = ['balance'];

    public function getBalanceAttribute()
    {
        // 1. Direct balance of this account
        $query = \App\Models\JournalItem::where('account_id', $this->id);
        $debit = $query->sum('debit');
        $credit = $query->sum('credit');

        $directBalance = 0;
        if (in_array($this->type, ['asset', 'expense'])) {
            $directBalance = $debit - $credit;
        } else {
            $directBalance = $credit - $debit;
        }

        // 2. Add balances of children recursively
        $childrenBalance = $this->children->sum('balance');

        return $directBalance + $childrenBalance;
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalItems()
    {
        return $this->hasMany(JournalItem::class, 'account_id');
    }
}
