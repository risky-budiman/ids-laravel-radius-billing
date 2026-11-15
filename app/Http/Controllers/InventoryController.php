<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryService;

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

        // Termasuk produk arsip: menghapus kategori menghapus produknya beserta riwayat (cascade)
        if ($category->allItems()->count() > 0) {
            return redirect()->back()->with('error', 'Kategori masih dipakai produk (termasuk produk yang diarsipkan).');
        }

        $category->delete();
        return redirect()->route('inventory.categories')->with('success', 'Category deleted successfully.');
    }

    public function stockIn()
    {
        $items = InventoryItem::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $customers = \App\Models\Customer::orderBy('name')->get();
        return view('inventory.stock-in', compact('items', 'suppliers', 'customers'));
    }

    public function storeStockIn(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'serials' => 'nullable|array',
            'serials.*' => 'nullable|string|max:100',
            'is_existing' => 'boolean',
            'customer_id' => 'required_if:is_existing,1|nullable|exists:customers,id',
            'payment_source' => 'nullable|in:bank,payable,none',
            'bank_account_id' => 'nullable|required_if:payment_source,bank|exists:bank_accounts,id',
        ], [], ['bank_account_id' => 'rekening pembayaran']);

        $item = InventoryItem::findOrFail($request->inventory_item_id);
        try {
            app(InventoryService::class)->stockIn($item, [
                'quantity' => $request->quantity, 'serials' => $request->serials ?? [],
                'unit_price' => $request->unit_price, 'tax_id' => $request->tax_id, 'supplier_id' => $request->supplier_id,
                'reference' => $request->reference, 'notes' => $request->notes,
                'existing_customer_id' => $request->boolean('is_existing') ? $request->customer_id : null,
                'payment_source' => $request->input('payment_source', 'payable'), 'bank_account_id' => $request->bank_account_id,
            ], $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['serials' => $e->getMessage()])->withInput();
        }

        return redirect()->route('inventory.index')->with('success', 'Stok masuk dicatat.');
    }

    public function stockOut()
    {
        $items = InventoryItem::orderBy('name')->get();
        $readyStocks = InventoryStock::available()->orderBy('serial_number')->get()->groupBy('inventory_item_id');

        return view('inventory.stock-out', compact('items', 'readyStocks'));
    }

    public function storeStockOut(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:infrastructure,new_line,maintenance,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'stock_ids' => 'nullable|array',
            'stock_ids.*' => 'integer',
        ]);

        $types = ['infrastructure' => 'Infrastruktur', 'new_line' => 'Pemasangan baru', 'maintenance' => 'Perbaikan', 'other' => 'Lainnya'];
        $item = InventoryItem::findOrFail($request->inventory_item_id);
        try {
            app(InventoryService::class)->stockOut($item, [
                'quantity' => $request->quantity, 'stock_ids' => $request->stock_ids ?? [],
                'reference' => trim(($request->reference ?? '') . ' (' . $types[$request->type] . ')'), 'notes' => $request->notes,
                // Infrastruktur -> aset Peralatan Jaringan; pemasangan -> beban operasional lapangan; lainnya -> beban maintenance
                'debit_account' => ['infrastructure' => '1202', 'new_line' => '5105', 'maintenance' => '5106', 'other' => '5106'][$request->type],
            ], $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return redirect()->route('inventory.index')->with('success', 'Stok keluar dicatat.');
    }

    /** Unit ber-SN: tandai rusak / hilang / siap pakai lagi. */
    public function setUnitStatus(Request $request, InventoryStock $stock)
    {
        $data = $request->validate(['status' => 'required|in:ready,damaged,lost', 'note' => 'nullable|string|max:200']);
        try {
            app(InventoryService::class)->setUnitStatus($stock, $data['status'], $request->user(), $data['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "SN {$stock->serial_number}: " . InventoryStock::STATUS_LABELS[$data['status']] . '.');
    }

    /** Stock opname: tandai SN hilang, daftarkan SN yang ditemukan / isi jumlah fisik, samakan catatan mutasi. */
    public function opname(InventoryItem $item)
    {
        return view('inventory.items.opname', [
            'item' => $item,
            'available' => $item->stocks()->available()->orderBy('serial_number')->get(),
            'balance' => $item->movementBalance(),
        ]);
    }

    public function storeOpname(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'present' => 'nullable|array', 'present.*' => 'integer',
            'found_serials' => 'nullable|string|max:20000',
            'physical_qty' => $item->track_serial ? 'nullable' : 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ], [], ['physical_qty' => 'jumlah fisik']);

        $missing = [];
        if ($item->track_serial) {
            // Yang tidak dicentang "ada" = hilang
            $missing = $item->stocks()->available()->whereNotIn('id', $data['present'] ?? [])->pluck('id')->all();
        }

        try {
            $r = app(InventoryService::class)->opname($item, $request->user(), $missing,
                InventoryService::parseSerials($data['found_serials'] ?? ''),
                $item->track_serial ? null : (float) $data['physical_qty'], $data['notes'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['found_serials' => $e->getMessage()])->withInput();
        }

        $fmt = fn ($n) => rtrim(rtrim(number_format(abs($n), 2, ',', '.'), '0'), ',');
        $parts = array_filter([
            $r['lost'] ? "{$r['lost']} SN ditandai hilang" : null,
            $r['registered'] ? "{$r['registered']} SN didaftarkan" : null,
            $r['adjustment'] != 0 ? 'penyesuaian ' . ($r['adjustment'] > 0 ? '+' : '−') . $fmt($r['adjustment']) . " {$item->unit}" : null,
        ]);

        return redirect()->route('inventory.show', $item)->with('success', 'Stock opname disimpan' . ($parts ? ': ' . implode(', ', $parts) . '.' : ' — stok sudah sesuai.'));
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
        $item->load(['category', 'stocks.customer']);
        $movements = $item->movements()->with('user', 'customer')->latest('id')->paginate(25);

        return view('inventory.items.show', compact('item', 'movements'));
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
            return redirect()->back()->with('error', 'Produk masih punya stok di gudang. Keluarkan atau sesuaikan lewat stock opname dulu.');
        }

        // Arsip (soft delete): riwayat mutasi & SN — termasuk yang terpasang di pelanggan — tetap tersimpan
        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'Produk diarsipkan; riwayatnya tetap tersimpan.');
    }
}
