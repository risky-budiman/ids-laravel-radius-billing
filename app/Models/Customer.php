<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    const STATUS_NEW = 'new';
    const STATUS_WAITING_ACTIVATION = 'waiting_activation';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_WAITING_DISMANTLE = 'waiting_dismantle';
    const STATUS_DISMANTLED = 'dismantled';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'customer_code',
        'region_code',
        'sto_code',
        'stb_code',
        'username',
        'ktp',
        'name',
        'email',
        'phone',
        'address',
        'latitude',
        'longitude',
        'package_id',
        'is_active',
        'status',
        'billing_type',
        'billing_method',
        'billing_day',
        'billing_due_day',
        'billing_next_date',
        'billing_due_date',
        'expired_at',
        'activated_at',
        'installation_fee',
        'installation_paid_at',
        'installation_bank_account_id',
        'activation_grace_expires_at',
        'use_tax',
    ];

    protected $casts = [
        'billing_next_date' => 'date',
        'billing_due_date' => 'date',
        'expired_at' => 'date',
        'activated_at' => 'datetime',
        'installation_paid_at' => 'datetime',
        'activation_grace_expires_at' => 'datetime',
        'installation_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'use_tax' => 'boolean',
    ];

    /**
     * Calculate prorata amount for the first month (Postpaid Cycle)
     */
    public function calculateProrata($price)
    {
        $now = now();
        $daysInMonth = $now->daysInMonth;
        $remainingDays = $daysInMonth - $now->day + 1; // Including today
        
        if ($remainingDays <= 0) return 0;
        
        return ($remainingDays / $daysInMonth) * $price;
    }

    /**
     * Sync and update the next billing dates based on type and method
     */
    public function syncBillingDates()
    {
        $now = now();
        
        if ($this->billing_type === 'postpaid') {
            if ($this->billing_method === 'cycle') {
                // Next invoice is 1st of next month, due 20th
                $this->billing_next_date = $now->copy()->addMonth()->startOfMonth();
                $this->billing_due_date = $this->billing_next_date->copy()->day($this->billing_due_day ?? 20);
            } elseif ($this->billing_method === 'fixed') {
                // Anniversary logic
                $anniversary = $this->activated_at ? $this->activated_at->day : $now->day;
                
                // If we don't have a due date yet, set it to next month anniversary
                if (!$this->billing_due_date) {
                    $this->billing_due_date = $now->copy()->addMonth()->day($anniversary);
                } else {
                    // Advance to next month
                    $this->billing_due_date = $this->billing_due_date->addMonth();
                }
                
                // Invoice generated -7 days before due date
                $this->billing_next_date = $this->billing_due_date->copy()->subDays(7);
            }
        } elseif ($this->billing_type === 'prepaid') {
            if ($this->billing_method === 'fixed') {
                $anniversary = $this->activated_at ? $this->activated_at->day : $now->day;
                
                if (!$this->billing_due_date) {
                    $this->billing_due_date = $now->copy()->addMonth()->day($anniversary);
                } else {
                    $this->billing_due_date = $this->billing_due_date->addMonth();
                }
                
                $this->billing_next_date = $this->billing_due_date->copy()->subDays(7);
                // Also update expired_at to match the end of the paid period
                $this->expired_at = $this->billing_due_date->copy();
            } elseif ($this->billing_method === 'renewal') {
                // Extend by 30 days
                $baseDate = ($this->expired_at && $this->expired_at->isFuture()) ? $this->expired_at : $now;
                $this->expired_at = $baseDate->copy()->addDays(30);
                
                // For renewal, next_date is maybe when we should remind them (e.g. 3 days before)
                $this->billing_next_date = $this->expired_at->copy()->subDays(3);
            }
        }
        
        $this->save();
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Auto-generation is now handled by the UI Form for better consistency
}
