<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\FixedAsset;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * List inventory items with stock calculations and categories.
     */
    public function index(Request $request)
    {
        $query = InventoryItem::with('category')->latest();

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        if ($request->filled('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }

        $items = $query->paginate(20);
        InventoryItem::withStockCounts($items->getCollection());

        $data = $items->getCollection()->map(function ($item) {
            $stock = $item->stock_count;
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'unit' => $item->unit,
                'category_name' => $item->category ? $item->category->name : 'Uncategorized',
                'min_stock' => (int) $item->min_stock,
                'stock_count' => $item->track_serial ? (int) $stock : (float) $stock,   // kabel per meter bisa desimal
                'is_low_stock' => $stock <= $item->min_stock,
                'track_serial' => (bool) $item->track_serial,
                'description' => $item->description,
            ];
        });

        $categories = InventoryCategory::select('id', 'name')->get();

        return response()->json([
            'stats' => [
                'total_items' => InventoryItem::count(),
                'low_stock_count' => (function () {
                    $all = InventoryItem::get(['id', 'track_serial', 'min_stock']);
                    InventoryItem::withStockCounts($all);

                    return $all->filter(fn ($i) => $i->stock_count <= $i->min_stock)->count();
                })(),
                'total_categories' => $categories->count(),
            ],
            'categories' => $categories,
            'items' => $data,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ]
        ]);
    }

    /**
     * Create new inventory item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:inventory_items,sku',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|integer|min:0',
            'track_serial' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $item = InventoryItem::create($validated);

        return response()->json([
            'message' => 'Barang inventory berhasil ditambahkan',
            'item_id' => $item->id,
        ], 201);
    }

    /**
     * Record Stock In or Stock Out.
     */
    public function recordMovement(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string',
            'serials' => 'nullable|array',
            'serials.*' => 'string|max:100',
        ]);

        // SN boleh dikirim sebagai teks (baris baru / koma) dan/atau array
        $serials = InventoryService::parseSerials($validated['serial_number'] ?? null, $validated['serials'] ?? []);
        $service = app(InventoryService::class);

        try {
            $payload = ['quantity' => $validated['quantity'], 'serials' => $serials,
                'notes' => $validated['notes'] ?? null, 'reference' => $validated['reference'] ?? null];
            $validated['type'] === 'in'
                ? $service->stockIn($item, $payload + ['journal' => false], $request->user())
                : $service->stockOut($item, $payload, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $typeLabel = $validated['type'] === 'in' ? 'Masuk' : 'Keluar';
        $snCountMsg = $serials ? ' (' . count($serials) . ' Serial Number dicatat)' : '';

        return response()->json([
            'message' => "Stok {$typeLabel} ({$validated['quantity']} {$item->unit}) berhasil dicatat.{$snCountMsg}",
            'current_stock' => $item->stock_count,
        ]);
    }

    /**
     * List fixed assets.
     */
    public function fixedAssets()
    {
        $assets = FixedAsset::latest()->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'name' => $a->name,
                'asset_code' => $a->asset_code,
                'purchase_date' => $a->purchase_date ? $a->purchase_date->format('Y-m-d') : null,
                'purchase_price' => (float) $a->purchase_price,
                'accumulated_depreciation' => (float) $a->accumulated_depreciation,
                'net_book_value' => (float) $a->net_book_value,
                'status' => $a->status,
                'description' => $a->description,
            ];
        });

        $totalAssetValue = $assets->sum('net_book_value');

        return response()->json([
            'total_asset_value' => $totalAssetValue,
            'assets' => $assets,
        ]);
    }

    /**
     * List suppliers.
     */
    public function suppliers()
    {
        $suppliers = Supplier::latest()->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
                'email' => $s->email,
                'address' => $s->address,
            ];
        });

        return response()->json([
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Get serial numbers (stocks) for an inventory item.
     */
    public function serials($id)
    {
        $item = InventoryItem::findOrFail($id);
        $stocks = $item->stocks()->with('customer')->latest()->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'serial_number' => $s->serial_number,
                'status' => $s->status,
                'condition' => $s->condition,
                'customer_name' => $s->customer ? $s->customer->name : null,
                'created_at' => $s->created_at ? $s->created_at->format('Y-m-d H:i') : null,
            ];
        });

        return response()->json([
            'item_name' => $item->name,
            'sku' => $item->sku,
            'total_serials' => $stocks->count(),
            'serials' => $stocks,
        ]);
    }
}
