<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankMutation extends Model
{
    public const STATUS_LABELS = [
        'matched' => 'Cocok otomatis', 'manual' => 'Dicocokkan manual',
        'unmatched' => 'Belum cocok', 'ignored' => 'Diabaikan',
    ];

    protected $fillable = [
        'provider', 'fingerprint', 'mutation_id', 'bank_id', 'type', 'amount', 'description',
        'mutation_at', 'status', 'invoice_id', 'note', 'handled_by', 'raw',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'mutation_at' => 'datetime',
        'raw' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
