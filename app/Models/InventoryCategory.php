<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'description'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    /** Termasuk produk yang diarsipkan — menghapus kategori ikut menghapus produknya (cascade). */
    public function allItems()
    {
        return $this->hasMany(InventoryItem::class, 'category_id')->withTrashed();
    }
}
