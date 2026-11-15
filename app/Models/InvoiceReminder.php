<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReminder extends Model
{
    public $timestamps = false;

    protected $fillable = ['invoice_id', 'days_before_due', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
