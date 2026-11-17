<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankStatementImport extends Model
{
    protected $fillable = ['bank_account_id', 'file_name', 'period_start', 'period_end', 'rows_total', 'rows_new',
        'opening_balance', 'closing_balance', 'created_by'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'opening_balance' => 'decimal:2', 'closing_balance' => 'decimal:2'];

    public function lines()
    {
        return $this->hasMany(BankStatementLine::class, 'import_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
