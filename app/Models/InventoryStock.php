<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'serial_number',
        'mac_address',
        'condition',
        'status',
        'customer_id'
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
