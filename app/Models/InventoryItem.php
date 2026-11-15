<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'unit',
        'description',
        'min_stock',
        'track_serial'
    ];

    protected $casts = ['track_serial' => 'boolean'];

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

    /**
     * Stok tersedia di gudang.
     *  - Barang ber-SN: jumlah unit berstatus tersedia (InventoryStock::AVAILABLE_STATUSES).
     *  - Barang tanpa SN: saldo mutasi (masuk − keluar ± penyesuaian).
     */
    public function getStockCountAttribute()
    {
        if ($this->track_serial) {
            return $this->stocks()->available()->count();
        }

        return $this->movementBalance();
    }

    /** Saldo menurut catatan mutasi (masuk − keluar + penyesuaian bertanda). */
    public function movementBalance(): float
    {
        $sums = $this->movements()->selectRaw('type, SUM(quantity) as qty')->groupBy('type')->pluck('qty', 'type');

        return round((float) ($sums['in'] ?? 0) - (float) ($sums['out'] ?? 0) + (float) ($sums['adjustment'] ?? 0), 2);
    }

    /**
     * Barang ber-SN: selisih catatan mutasi vs unit SN di gudang. Positif = ada unit tercatat masuk
     * tapi SN-nya belum didaftarkan; negatif = ada SN tanpa catatan masuk. 0 = cocok.
     */
    public function serialDiscrepancy(): float
    {
        return $this->track_serial ? round($this->movementBalance() - $this->stocks()->available()->count(), 2) : 0.0;
    }
}
