<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::with('category')->paginate(10);
        $categories = InventoryCategory::all();
        $totalItems = InventoryItem::count();
        $lowStockItems = InventoryItem::all()->filter(function($item) {
            return $item->stock_count <= $item->min_stock;
        })->count();
        
        return view('inventory.items.index', compact('items', 'categories', 'totalItems', 'lowStockItems'));
    }

    public function create()
    {
        $categories = InventoryCategory::all();
        return view('inventory.items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:inventory_items,sku',
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|integer|min:0',
            'track_serial' => 'boolean',
            'description' => 'nullable|string',
        ]);

        InventoryItem::create($validated);
        return redirect()->route('inventory.index')->with('success', 'Product created successfully.');
    }

    public function categories()
    {
        $categories = InventoryCategory::withCount('items')->get();
        return view('inventory.categories.index', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        InventoryCategory::create($validated);
        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function stockIn()
    {
        $items = InventoryItem::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        return view('inventory.stock-in', compact('items', 'suppliers'));
    }

    public function storeStockIn(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:1',
            'reference' => 'nullable|string',
            'serials' => 'nullable|array', // For SN tracking
        ]);

        $item = InventoryItem::findOrFail($request->inventory_item_id);

        DB::transaction(function() use ($request, $item) {
            // 1. Create movement log
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $request->quantity,
                'reference' => $request->reference,
                'user_id' => Auth::id()
            ]);

            // 2. If track SN, create individual stocks
            if ($item->track_serial && $request->has('serials')) {
                foreach ($request->serials as $sn) {
                    if (!empty($sn)) {
                        InventoryStock::create([
                            'inventory_item_id' => $item->id,
                            'serial_number' => $sn,
                            'status' => 'ready'
                        ]);
                    }
                }
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Stock added successfully.');
    }

    public function show(InventoryItem $item)
    {
        $item->load(['category', 'stocks', 'movements.user']);
        return view('inventory.items.show', compact('item'));
    }

    // Standard resource stubs updated with redirects
    public function edit($id) { return redirect()->route('inventory.index'); }
    public function update(Request $request, $id) { return redirect()->route('inventory.index'); }
    public function destroy($id) { return redirect()->route('inventory.index'); }
}
