<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    protected $fillable = ['journal_id', 'account_id', 'debit', 'credit'];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
