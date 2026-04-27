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

        $validated['track_serial'] = $request->has('track_serial');
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
        return redirect()->route('inventory.categories')->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only administrators can edit categories.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);
        return redirect()->route('inventory.categories')->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(InventoryCategory $category)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only administrators can delete categories.');
        }

        if ($category->items()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category that has items.');
        }

        $category->delete();
        return redirect()->route('inventory.categories')->with('success', 'Category deleted successfully.');
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
            'unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference' => 'nullable|string',
            'serials' => 'nullable|array', // For SN tracking
        ]);

        $item = InventoryItem::findOrFail($request->inventory_item_id);

        DB::transaction(function() use ($request, $item) {
            $subtotal = $request->unit_price * $request->quantity;
            $taxAmount = 0;
            if ($request->filled('tax_id')) {
                $tax = \App\Models\Tax::find($request->tax_id);
                $taxAmount = ($subtotal * $tax->rate) / 100;
            }
            $totalAmount = $subtotal + $taxAmount;

            // 1. Create movement log
            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'subtotal' => $subtotal,
                'tax_id' => $request->tax_id,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'supplier_id' => $request->supplier_id,
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

            // 3. Auto-Journal: Inventory Purchase
            try {
                (new \App\Services\AccountingService())->recordInventoryPurchase($movement);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Auto-journal failed for STOCK-IN-{$movement->id}: " . $e->getMessage());
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Stock added successfully.');
    }

    public function stockOut()
    {
        $items = InventoryItem::orderBy('name')->get();
        // Get only ready or returned stocks for SN items
        $readyStocks = InventoryStock::whereIn('status', ['ready', 'returned'])->get()->groupBy('inventory_item_id');
        
        return view('inventory.stock-out', compact('items', 'readyStocks'));
    }

    public function storeStockOut(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:1',
            'type' => 'required|in:infrastructure,new_line,maintenance,other',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
            'stock_ids' => 'nullable|array', // For SN tracking
        ]);

        $item = InventoryItem::findOrFail($request->inventory_item_id);
        
        // Validation for stock availability
        if ($item->stock_count < $request->quantity) {
            return back()->withErrors(['quantity' => 'Insufficient stock. current stock: ' . $item->stock_count]);
        }

        DB::transaction(function() use ($request, $item) {
            // 1. Create movement log
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'out',
                'quantity' => $request->quantity,
                'reference' => $request->reference . ' (' . $request->type . ')',
                'notes' => $request->notes,
                'user_id' => Auth::id()
            ]);

            // 2. If track SN, mark selected stocks as used
            if ($item->track_serial && $request->has('stock_ids')) {
                InventoryStock::whereIn('id', $request->stock_ids)
                    ->update(['status' => 'used']);
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Stock out processed successfully.');
    }

    public function outflowReport()
    {
        $outflows = InventoryMovement::with(['item', 'user', 'customer'])
            ->where('type', 'out')
            ->orderBy('created_at', 'desc')
            ->paginate(1000); // Set high enough to avoid pagination in audit reports
            
        return view('inventory.outflow', compact('outflows'));
    }

    public function show(InventoryItem $item)
    {
        $item->load(['category', 'stocks', 'movements.user']);
        return view('inventory.items.show', compact('item'));
    }

    // Standard resource stubs updated with redirects
    public function edit(InventoryItem $item)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->route('inventory.index')->with('error', 'Unauthorized action.');
        }
        $categories = InventoryCategory::all();
        return view('inventory.items.edit', compact('item', 'categories'));
    }

    public function createCategory()
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->route('inventory.categories')->with('error', 'Unauthorized action.');
        }
        return view('inventory.categories.create');
    }

    public function editCategory(InventoryCategory $category)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->route('inventory.categories')->with('error', 'Unauthorized action.');
        }
        return view('inventory.categories.edit', compact('category'));
    }
    
    public function update(Request $request, InventoryItem $item)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only administrators can edit products.');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:inventory_items,sku,' . $item->id,
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|integer|min:0',
            'track_serial' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['track_serial'] = $request->has('track_serial');
        $item->update($validated);
        return redirect()->route('inventory.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(InventoryItem $item)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only administrators can delete products.');
        }

        if ($item->stock_count > 0) {
            return redirect()->back()->with('error', 'Cannot delete product that still has stock.');
        }

        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'Product deleted successfully.');
    }
}
