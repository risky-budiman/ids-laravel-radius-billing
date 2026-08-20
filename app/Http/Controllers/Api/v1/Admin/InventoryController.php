<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\FixedAsset;
use App\Models\Supplier;
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
            $query->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
        }

        if ($request->filled('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }

        $items = $query->paginate(20);

        $data = $items->getCollection()->map(function ($item) {
            $stock = $item->stock_count;
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'unit' => $item->unit,
                'category_name' => $item->category ? $item->category->name : 'Uncategorized',
                'min_stock' => (int) $item->min_stock,
                'stock_count' => (int) $stock,
                'is_low_stock' => $stock <= $item->min_stock,
                'track_serial' => (bool) $item->track_serial,
                'description' => $item->description,
            ];
        });

        $categories = InventoryCategory::select('id', 'name')->get();

        return response()->json([
            'stats' => [
                'total_items' => InventoryItem::count(),
                'low_stock_count' => InventoryItem::all()->filter(fn ($i) => $i->stock_count <= $i->min_stock)->count(),
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
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'serial_number' => 'nullable|string',
        ]);

        $movement = $item->movements()->create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($item->track_serial && !empty($validated['serial_number'])) {
            if ($validated['type'] === 'in') {
                $item->stocks()->create([
                    'serial_number' => $validated['serial_number'],
                    'status' => 'ready',
                ]);
            } else {
                $stock = $item->stocks()->where('serial_number', $validated['serial_number'])->first();
                if ($stock) {
                    $stock->update(['status' => 'deployed']);
                }
            }
        }

        $typeLabel = $validated['type'] === 'in' ? 'Masuk' : 'Keluar';

        return response()->json([
            'message' => "Stok {$typeLabel} ({$validated['quantity']} {$item->unit}) berhasil dicatat.",
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
}
