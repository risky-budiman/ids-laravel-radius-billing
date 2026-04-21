<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'unit',
        'description',
        'min_stock',
        'track_serial'
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getStockCountAttribute()
    {
        if ($this->track_serial) {
            return $this->stocks()->whereIn('status', ['ready', 'returned'])->count();
        }
        
        return $this->movements()->where('type', 'in')->sum('quantity') - 
               $this->movements()->where('type', 'out')->sum('quantity');
    }
}
