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

    /** Diisi oleh withStockCounts() agar daftar tidak menghitung stok per produk (2 query tiap produk). */
    public ?float $preloadedStock = null;

    /**
     * Hitung stok banyak produk sekaligus (2 query total) dan simpan di masing-masing produk.
     *
     * @param  iterable<InventoryItem>  $items
     */
    public static function withStockCounts(iterable $items): void
    {
        $items = collect($items);
        $ids = $items->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        $serial = InventoryStock::available()->whereIn('inventory_item_id', $ids)
            ->selectRaw('inventory_item_id, COUNT(*) c')->groupBy('inventory_item_id')->pluck('c', 'inventory_item_id');
        $ledger = InventoryMovement::whereIn('inventory_item_id', $ids)
            ->selectRaw("inventory_item_id, SUM(CASE WHEN type = 'out' THEN -quantity ELSE quantity END) q")
            ->groupBy('inventory_item_id')->pluck('q', 'inventory_item_id');
        foreach ($items as $item) {
            $item->preloadedStock = $item->track_serial ? (float) ($serial[$item->id] ?? 0) : round((float) ($ledger[$item->id] ?? 0), 2);
        }
    }

    /**
     * Stok tersedia di gudang.
     *  - Barang ber-SN: jumlah unit berstatus tersedia (InventoryStock::AVAILABLE_STATUSES).
     *  - Barang tanpa SN: saldo mutasi (masuk − keluar ± penyesuaian).
     */
    public function getStockCountAttribute()
    {
        if ($this->preloadedStock !== null) {
            return $this->track_serial ? (int) $this->preloadedStock : $this->preloadedStock;
        }
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
