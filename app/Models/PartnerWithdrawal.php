<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerWithdrawal extends Model
{
    protected $fillable = [
        'partner_id',
        'amount',
        'request_date',
        'payment_date',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'processed_by',
        'journal_id',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function commissions()
    {
        return $this->hasMany(PartnerCommission::class, 'withdrawal_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
