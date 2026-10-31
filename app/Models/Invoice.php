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
        'discount_type',
        'discount_value',
        'discount_amount',
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
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
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

    /**
     * Generate a guaranteed unique, collision-proof invoice number
     */
    public static function generateUniqueInvoiceNumber(string $prefix = 'INV'): string
    {
        do {
            $number = $prefix . '-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('invoice_number', $number)->exists());

        return $number;
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateUniqueInvoiceNumber();
            } else {
                // Auto-resolve collision if number already exists
                while (static::where('invoice_number', $invoice->invoice_number)->exists()) {
                    $prefix = str_starts_with($invoice->invoice_number, 'INV-INST-') ? 'INV-INST' : 'INV';
                    $invoice->invoice_number = static::generateUniqueInvoiceNumber($prefix);
                }
            }
        });

        static::deleted(function ($invoice) {
            // Remove associated accrual & payment journals to prevent inflated balances
            \App\Models\Journal::where('reference', 'BILL-' . $invoice->invoice_number)->delete();
            \App\Models\Journal::whereIn('reference', [
                'PAY-' . $invoice->invoice_number,
                'REV-' . $invoice->invoice_number
            ])->delete();
            
            // Remove associated bank transaction if exists
            \App\Models\BankTransaction::where('description', 'like', '%[Pembayaran Invoice] ' . $invoice->invoice_number . '%')->get()->each->delete();
        });
    }
}
