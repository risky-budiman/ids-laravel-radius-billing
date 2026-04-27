<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_id',
        'tax_amount',
        'total_amount',
        'supplier_id',
        'reference',
        'notes',
        'user_id',
        'customer_id',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
