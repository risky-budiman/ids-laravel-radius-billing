<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use LogsActivity;
    protected $fillable = [
        'customer_id',
        'invoice_number',
        'billing_period',
        'period_start',
        'period_end',
        'amount',
        'subtotal',
        'tax_id',
        'tax_amount',
        'status',
        'payment_url',
        'payment_token',
        'payment_method',
        'due_date',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    protected static function booted()
    {
        static::deleted(function ($invoice) {
            // Remove associated accrual & payment journals to prevent inflated balances
            \App\Models\Journal::where('reference', 'BILL-' . $invoice->invoice_number)->delete();
            \App\Models\Journal::where('reference', 'PAY-' . $invoice->invoice_number)->delete();
            
            // Remove associated bank transaction if exists
            $tx = \App\Models\BankTransaction::where('description', 'like', '%[Pembayaran Invoice] ' . $invoice->invoice_number . '%')->first();
            if ($tx) $tx->delete();
        });
    }
}
