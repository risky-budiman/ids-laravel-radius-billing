<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    /**
     * Status unit yang ada di gudang & boleh dipakai: baru masuk, retur, atau ditarik dari pelanggan
     * (dismantle). Satu-satunya definisi "stok tersedia" — dipakai Gudang, Stok Keluar, API & aktivasi.
     */
    public const AVAILABLE_STATUSES = ['ready', 'returned', 'dismantled'];

    public const STATUS_LABELS = [
        'ready' => 'Siap pakai', 'returned' => 'Retur', 'dismantled' => 'Hasil tarik',
        'installed' => 'Terpasang', 'damaged' => 'Rusak', 'lost' => 'Hilang',
    ];

    public const CONDITION_LABELS = ['new' => 'Baru', 'used' => 'Bekas', 'refurbished' => 'Rekondisi', 'broken' => 'Rusak'];

    protected $fillable = [
        'inventory_item_id',
        'serial_number',
        'mac_address',
        'condition',
        'status',
        'customer_id'
    ];

    /** Unit di gudang yang bisa dikeluarkan (bukan yang kondisinya rusak). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereIn('status', self::AVAILABLE_STATUSES)
            ->whereNull('customer_id')
            ->where(fn ($q) => $q->whereNull('condition')->orWhere('condition', '!=', 'broken'));
    }

    public function isAvailable(): bool
    {
        return in_array($this->status, self::AVAILABLE_STATUSES, true) && ! $this->customer_id && $this->condition !== 'broken';
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
