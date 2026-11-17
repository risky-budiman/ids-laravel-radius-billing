<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu baris mutasi rekening dari bank (hasil import), dicocokkan dengan transaksi Kas & Bank aplikasi. */
class BankStatementLine extends Model
{
    protected $fillable = ['import_id', 'bank_account_id', 'line_date', 'description', 'amount', 'balance', 'fingerprint',
        'status', 'match_type', 'bank_transaction_id', 'note', 'handled_by'];

    protected $casts = ['line_date' => 'date', 'amount' => 'decimal:2', 'balance' => 'decimal:2'];

    public function transaction()
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function import()
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }

    public function isCredit(): bool
    {
        return (float) $this->amount > 0;
    }
}
