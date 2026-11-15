<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Satu jalur untuk stok masuk, stok keluar & stock opname (web, API aplikasi, Purchase Order).
 * Aturan:
 *  - Barang ber-SN: setiap unit masuk/keluar wajib punya SN; SN unik; yang dikeluarkan harus milik
 *    produk itu dan berstatus tersedia. Baris dikunci saat disimpan agar tidak dipakai dua kali.
 *  - Barang tanpa SN: tidak boleh keluar melebihi saldo.
 *  - Setiap perubahan stok tercatat sebagai mutasi (in / out / adjustment).
 *
 * @throws \DomainException  pesan siap tampil untuk pengguna
 */
class InventoryService
{
    /** Normalisasi daftar SN dari array dan/atau teks (baris baru / koma). */
    public static function parseSerials(array|string|null ...$sources): array
    {
        $out = [];
        foreach ($sources as $src) {
            $list = is_array($src) ? $src : preg_split('/[\r\n,;]+/', (string) $src);
            foreach ($list as $sn) {
                $sn = trim((string) $sn);
                if ($sn !== '') {
                    $out[strtoupper($sn)] = $sn;   // unik, tidak peka huruf besar/kecil
                }
            }
        }

        return array_values($out);
    }

    /**
     * @param  array{quantity?: float|int, serials?: array, unit_price?: float, tax_id?: ?int, supplier_id?: ?int,
     *               reference?: ?string, notes?: ?string, existing_customer_id?: ?int, condition?: string,
     *               journal?: bool, serials_later?: bool}  $d
     */
    public function stockIn(InventoryItem $item, array $d, ?User $by): InventoryMovement
    {
        $serials = self::parseSerials($d['serials'] ?? []);
        // serials_later: penerimaan Purchase Order — SN didaftarkan kemudian lewat stock opname
        $quantity = $this->quantity($item, $d['quantity'] ?? count($serials), $serials, 'masuk', ! empty($d['serials_later']) && ! $serials);
        $existingCustomer = $d['existing_customer_id'] ?? null;

        if ($item->track_serial) {
            $taken = InventoryStock::whereIn(DB::raw('UPPER(serial_number)'), array_map('strtoupper', $serials))->pluck('serial_number');
            if ($taken->isNotEmpty()) {
                throw new \DomainException('SN sudah terdaftar: ' . $taken->implode(', ') . '.');
            }
        }

        return DB::transaction(function () use ($item, $d, $by, $serials, $quantity, $existingCustomer) {
            $unitPrice = (float) ($d['unit_price'] ?? 0);
            $subtotal = $unitPrice * $quantity;
            $taxAmount = ! empty($d['tax_id']) ? round($subtotal * (float) (Tax::find($d['tax_id'])?->rate ?? 0) / 100, 2) : 0;

            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => $quantity,
                'unit_price' => $unitPrice, 'subtotal' => $subtotal, 'tax_id' => $d['tax_id'] ?? null,
                'tax_amount' => $taxAmount, 'total_amount' => $subtotal + $taxAmount, 'supplier_id' => $d['supplier_id'] ?? null,
                'reference' => $d['reference'] ?? null,
                'notes' => $existingCustomer ? 'Perangkat lama yang sudah terpasang di pelanggan' : ($d['notes'] ?? null),
                'user_id' => $by?->id,
            ]);

            foreach ($serials as $sn) {
                InventoryStock::create([
                    'inventory_item_id' => $item->id, 'serial_number' => $sn,
                    'condition' => $existingCustomer ? 'used' : ($d['condition'] ?? 'new'),
                    'status' => $existingCustomer ? 'installed' : 'ready',
                    'customer_id' => $existingCustomer,
                ]);
            }

            // Perangkat lama di pelanggan: langsung tercatat keluar ke pelanggan tsb
            if ($existingCustomer) {
                InventoryMovement::create([
                    'inventory_item_id' => $item->id, 'type' => 'out', 'quantity' => $quantity, 'customer_id' => $existingCustomer,
                    'reference' => 'Perangkat lama di pelanggan', 'notes' => 'Dicatat saat stok masuk', 'user_id' => $by?->id,
                ]);
            }

            if ($d['journal'] ?? true) {
                try {
                    app(AccountingService::class)->recordInventoryPurchase($movement);
                } catch (\Throwable $e) {
                    Log::error("Auto-journal failed for STOCK-IN-{$movement->id}: " . $e->getMessage());
                }
            }

            return $movement;
        });
    }

    /**
     * @param  array{quantity?: float|int, stock_ids?: array, serials?: array, reference?: ?string, notes?: ?string,
     *               customer_id?: ?int}  $d  barang ber-SN: pilih unit lewat stock_ids ATAU serials
     */
    public function stockOut(InventoryItem $item, array $d, ?User $by): InventoryMovement
    {
        return DB::transaction(function () use ($item, $d, $by) {
            // Kunci produk: dua pengeluaran bersamaan tidak boleh sama-sama lolos cek saldo
            InventoryItem::whereKey($item->id)->lockForUpdate()->first();

            $stocks = collect();
            if ($item->track_serial) {
                $stocks = $this->pickSerialStocks($item, $d);
                $quantity = $stocks->count();
                if (isset($d['quantity']) && (float) $d['quantity'] != $quantity) {
                    throw new \DomainException("Jumlah ({$d['quantity']}) harus sama dengan jumlah SN yang dipilih ({$quantity}).");
                }
            } else {
                $quantity = $this->quantity($item, $d['quantity'] ?? 0, [], 'keluar');
                $balance = $item->movementBalance();
                if ($quantity > $balance) {
                    throw new \DomainException("Stok {$item->name} tidak cukup: tersedia " . $this->fmt($balance) . " {$item->unit}, diminta " . $this->fmt($quantity) . '.');
                }
            }

            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id, 'type' => 'out', 'quantity' => $quantity,
                'customer_id' => $d['customer_id'] ?? null, 'reference' => $d['reference'] ?? null,
                'notes' => trim(($d['notes'] ?? '') . ($stocks->isNotEmpty() ? ' (SN: ' . $stocks->pluck('serial_number')->implode(', ') . ')' : '')) ?: null,
                'user_id' => $by?->id,
            ]);

            foreach ($stocks as $stock) {
                $stock->update(['status' => 'installed', 'customer_id' => $d['customer_id'] ?? null]);
            }

            return $movement;
        });
    }

    /** Unit ber-SN yang akan dikeluarkan — terkunci, milik produk ini, dan tersedia. */
    private function pickSerialStocks(InventoryItem $item, array $d): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $d['stock_ids'] ?? []))));
        $serials = self::parseSerials($d['serials'] ?? []);
        if (! $ids && ! $serials) {
            throw new \DomainException("{$item->name} dilacak per SN: pilih/isi SN unit yang dikeluarkan.");
        }

        $query = InventoryStock::where('inventory_item_id', $item->id)->lockForUpdate();
        $stocks = $ids ? $query->whereIn('id', $ids)->get() : $query->whereIn(DB::raw('UPPER(serial_number)'), array_map('strtoupper', $serials))->get();

        $requested = $ids ?: array_map('strtoupper', $serials);
        $found = $ids ? $stocks->pluck('id')->all() : $stocks->pluck('serial_number')->map(fn ($s) => strtoupper($s))->all();
        $missing = array_diff($requested, $found);
        if ($missing) {
            throw new \DomainException('SN tidak ditemukan pada produk ' . $item->name . ($ids ? '.' : ': ' . implode(', ', $missing) . '.'));
        }
        $unavailable = $stocks->reject->isAvailable();
        if ($unavailable->isNotEmpty()) {
            throw new \DomainException('SN tidak tersedia di gudang: ' . $unavailable->map(fn ($s) => $s->serial_number . ' (' . (InventoryStock::STATUS_LABELS[$s->status] ?? $s->status) . ')')->implode(', ') . '.');
        }

        return $stocks;
    }

    /**
     * Stock opname.
     *  - Ber-SN: $missingStockIds -> status hilang; $foundSerials -> didaftarkan sebagai unit siap pakai.
     *  - Tanpa SN: $physicalQty = jumlah fisik.
     * Setelah itu catatan mutasi disamakan dengan stok nyata lewat satu mutasi 'adjustment' (bila ada selisih).
     *
     * @return array{adjustment: float, lost: int, registered: int}
     */
    public function opname(InventoryItem $item, ?User $by, array $missingStockIds = [], array $foundSerials = [], ?float $physicalQty = null, ?string $notes = null): array
    {
        $foundSerials = self::parseSerials($foundSerials);

        return DB::transaction(function () use ($item, $by, $missingStockIds, $foundSerials, $physicalQty, $notes) {
            InventoryItem::whereKey($item->id)->lockForUpdate()->first();
            $lost = 0;
            $registered = 0;

            if ($item->track_serial) {
                if ($missingStockIds) {
                    $lost = InventoryStock::where('inventory_item_id', $item->id)->available()
                        ->whereIn('id', $missingStockIds)->update(['status' => 'lost']);
                }
                if ($foundSerials) {
                    $taken = InventoryStock::whereIn(DB::raw('UPPER(serial_number)'), array_map('strtoupper', $foundSerials))->pluck('serial_number');
                    if ($taken->isNotEmpty()) {
                        throw new \DomainException('SN sudah terdaftar: ' . $taken->implode(', ') . '.');
                    }
                    foreach ($foundSerials as $sn) {
                        InventoryStock::create(['inventory_item_id' => $item->id, 'serial_number' => $sn, 'condition' => 'used', 'status' => 'ready']);
                    }
                    $registered = count($foundSerials);
                }
                $actual = (float) $item->stocks()->available()->count();
            } else {
                if ($physicalQty === null || $physicalQty < 0) {
                    throw new \DomainException('Isi jumlah fisik di gudang.');
                }
                $actual = round($physicalQty, 2);
            }

            $adjustment = round($actual - $item->movementBalance(), 2);
            if ($adjustment != 0.0) {
                InventoryMovement::create([
                    'inventory_item_id' => $item->id, 'type' => 'adjustment', 'quantity' => $adjustment,
                    'reference' => 'Stock opname',
                    'notes' => trim(($notes ? $notes . ' — ' : '') . ($lost ? "{$lost} SN hilang. " : '') . ($registered ? "{$registered} SN didaftarkan. " : '')
                        . 'Catatan disamakan dengan stok fisik (' . $this->fmt($actual) . " {$item->unit}).") ?: null,
                    'user_id' => $by?->id,
                ]);
            }

            return ['adjustment' => $adjustment, 'lost' => $lost, 'registered' => $registered];
        });
    }

    private function quantity(InventoryItem $item, $quantity, array $serials, string $dir, bool $serialsLater = false): float
    {
        $quantity = round((float) $quantity, 2);
        if ($item->track_serial && ! $serialsLater) {
            if (! $serials) {
                throw new \DomainException("{$item->name} dilacak per SN: isi SN untuk setiap unit {$dir}.");
            }
            if ($quantity != count($serials)) {
                throw new \DomainException('Jumlah (' . $this->fmt($quantity) . ') harus sama dengan jumlah SN (' . count($serials) . ').');
            }
        }
        if ($quantity <= 0) {
            throw new \DomainException('Jumlah harus lebih dari 0.');
        }

        return $quantity;
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }
}
