<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationReport extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'technician_id', 'snapshot', 'terms', 'sign_token',
        'customer_signature', 'customer_signer_name', 'customer_signed_at',
        'technician_signature', 'technician_signed_at', 'sign_meta',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'sign_meta' => 'array',
        'customer_signed_at' => 'datetime',
        'technician_signed_at' => 'datetime',
    ];

    protected $hidden = ['sign_token'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function isFullySigned(): bool
    {
        return $this->customer_signed_at !== null && $this->technician_signed_at !== null;
    }

    /** Tautan publik untuk pelanggan melihat & menandatangani BA. */
    public function signUrl(): string
    {
        return route('installation-report.public', $this->sign_token);
    }
}
