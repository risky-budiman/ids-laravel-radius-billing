<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')->latest()->paginate(15);
        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $inventoryItems = InventoryItem::all();
        return view('purchase-orders.create', compact('suppliers', 'inventoryItems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // Create PO
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'due_date' => $validated['due_date'],
                'status' => 'received', // Auto-receive per user request
                'total_amount' => $totalAmount,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'],
            ]);

            // Create PO Items and Update Inventory
            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // Inventory In (create stock)
                for ($i = 0; $i < $item['quantity']; $i++) {
                    InventoryStock::create([
                        'inventory_item_id' => $item['inventory_item_id'],
                        'status' => 'ready',
                        'condition' => 'new',
                        // Optional: Generate serial number if track_serial is true
                    ]);
                }
            }

            // Auto Journal Entry (Debit Inventory, Credit AP)
            $inventoryAccount = ChartOfAccount::where('code', '1140')->first(); // Persediaan
            $apAccount = ChartOfAccount::where('code', '2110')->first(); // Hutang Usaha

            if ($inventoryAccount && $apAccount && $totalAmount > 0) {
                $journal = Journal::create([
                    'journal_number' => 'JRN-PO-' . $purchaseOrder->id,
                    'date' => $validated['date'],
                    'reference' => 'Pembelian via ' . $poNumber,
                    'description' => 'Penerimaan barang dari PO',
                    'status' => 'posted',
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $inventoryAccount->id,
                    'description' => 'Persediaan Masuk',
                    'debit' => $totalAmount,
                    'credit' => 0,
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $apAccount->id,
                    'description' => 'Hutang Vendor',
                    'debit' => 0,
                    'credit' => $totalAmount,
                ]);
            }

            DB::commit();

            // Send notification to Finance and Administrators
            $financeUsers = \App\Models\User::whereIn('role', ['administrator', 'admin', 'kasir'])
                ->where('is_active', true)
                ->get();
                
            foreach ($financeUsers as $financeUser) {
                $financeUser->notify(new \App\Notifications\PurchaseOrderCreatedNotification($purchaseOrder));
            }

            return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order created, items received, and accounts payable recorded.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat PO: ' . $e->getMessage());
        }
    }
}
