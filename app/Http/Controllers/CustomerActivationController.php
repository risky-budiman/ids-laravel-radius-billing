<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CustomerActivationController extends Controller
{
    public function index(Customer $customer)
    {
        // Get items that are tracked by serial number and have ready or dismantled stocks
        $serialItems = InventoryItem::where('track_serial', true)
            ->whereHas('stocks', function($q) {
                $q->whereIn('status', ['ready', 'dismantled']);
            })->with(['stocks' => function($q) {
                $q->whereIn('status', ['ready', 'dismantled']);
            }])->get();

        // Get items that are not tracked (cables, connectors, etc)
        $consumableItems = InventoryItem::where('track_serial', false)->get();

        return view('customers.activate', compact('customer', 'serialItems', 'consumableItems'));
    }

    public function store(Request $request, Customer $customer)
    {
        $request->validate([
            'modem_stock_id' => 'required|exists:inventory_stocks,id',
            'consumables' => 'nullable|array',
            'consumables.*.item_id' => 'required|exists:inventory_items,id',
            'consumables.*.quantity' => 'required|numeric|min:0',
        ]);

        DB::transaction(function() use ($request, $customer) {
            // 1. Activate Customer and Set Initial Billing
            $customer->update([
                'is_active' => true,
                'status' => Customer::STATUS_ACTIVE,
                'activated_at' => now(),
            ]);

            // Handle Initial Invoicing for Postpaid Cycle
            if ($customer->billing_type === 'postpaid' && $customer->billing_method === 'cycle') {
                $prorataAmount = $customer->calculateProrata($customer->package->price);
                
                if ($prorataAmount > 0) {
                    \App\Models\Invoice::create([
                        'invoice_number' => 'INV-PR-' . strtoupper(uniqid()),
                        'customer_id' => $customer->id,
                        'amount' => $prorataAmount,
                        'status' => 'unpaid',
                        'due_date' => now()->day($customer->billing_due_day ?? 20),
                        'description' => 'Tagihan Prorata (Aktivasi Baru)',
                    ]);
                }
            }

            // Sync next billing dates
            $customer->syncBillingDates();

            // 2. Find and Close Aktivasi Ticket
            \App\Models\Ticket::where('customer_id', $customer->id)
                ->where('type', 'aktivasi')
                ->whereIn('status', ['open', 'in_progress'])
                ->update([
                    'status' => 'closed',
                    'resolution_notes' => 'Aktivasi selesai. Perangkat telah dipasang.'
                ]);

            // 3. Install Modem (Serialized Item)
            $modem = InventoryStock::findOrFail($request->modem_stock_id);
            $modem->update([
                'status' => 'installed',
                'customer_id' => $customer->id
            ]);

            // Record movement for the modem
            InventoryMovement::create([
                'inventory_item_id' => $modem->inventory_item_id,
                'customer_id' => $customer->id,
                'type' => 'out',
                'quantity' => 1,
                'reference' => 'Activation: ' . $customer->customer_code,
                'notes' => 'Installed at Customer: ' . $customer->name . ' (SN: ' . $modem->serial_number . ')',
                'user_id' => Auth::id()
            ]);

            // 3. Process Consumables (Cables, etc)
            if ($request->has('consumables')) {
                foreach ($request->consumables as $cons) {
                    if ($cons['quantity'] > 0) {
                        InventoryMovement::create([
                            'inventory_item_id' => $cons['item_id'],
                            'customer_id' => $customer->id,
                            'type' => 'out',
                            'quantity' => $cons['quantity'],
                            'reference' => 'Activation: ' . $customer->customer_code,
                            'notes' => 'Consumed for installation',
                            'user_id' => Auth::id()
                        ]);
                    }
                }
            }
        });

        return redirect()->route('customers.index')->with('success', 'Customer activated and equipment recorded successfully.');
    }

    public function dismantleForm(Customer $customer)
    {
        $installedEquipment = InventoryStock::where('customer_id', $customer->id)
            ->where('status', 'installed')
            ->with('item')
            ->get();

        return view('customers.dismantle', compact('customer', 'installedEquipment'));
    }

    public function processDismantle(Request $request, Customer $customer)
    {
        $request->validate([
            'stock_ids' => 'required|array',
            'stock_ids.*' => 'exists:inventory_stocks,id',
        ]);

        try {
            DB::transaction(function() use ($request, $customer) {
                // 1. Update Customer status to dismantled
                $customer->update([
                    'is_active' => false,
                    'status' => Customer::STATUS_DISMANTLED
                ]);

                // 2. Find and Close Dismantle Ticket
                \App\Models\Ticket::where('customer_id', $customer->id)
                    ->where('type', 'dismantle')
                    ->whereIn('status', ['open', 'in_progress'])
                    ->update([
                        'status' => 'closed',
                        'resolution_notes' => 'Dismantle selesai. Perangkat telah ditarik.'
                    ]);

                // 3. Process each dismantled equipment
                foreach ($request->stock_ids as $stockId) {
                    $stock = InventoryStock::findOrFail($stockId);
                    
                    // Track current serial for the log
                    $sn = $stock->serial_number;

                    // Update stock status
                    $stock->update([
                        'status' => 'dismantled', 
                        'customer_id' => null,
                        'condition' => 'used'
                    ]);

                    // Create movement log for return (IN)
                    InventoryMovement::create([
                        'inventory_item_id' => $stock->inventory_item_id,
                        'customer_id' => $customer->id,
                        'type' => 'in',
                        'quantity' => 1,
                        'reference' => 'Dismantle: ' . ($customer->customer_code ?? 'Manual'),
                        'notes' => 'Equipment returned (SN: ' . $sn . ')',
                        'user_id' => Auth::id() ?? 1 // Fallback to user 1 if no session (should not happen)
                    ]);
                }
            });

            return redirect()->route('customers.index')->with('success', 'Customer dismantled and equipment returned to stock.');
        } catch (\Exception $e) {
            \Log::error('Dismantle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses dismantle: ' . $e->getMessage());
        }
    }

    public function requestDismantle(Customer $customer)
    {
        $customer->update(['status' => Customer::STATUS_WAITING_DISMANTLE]);
        return redirect()->back()->with('success', 'Permintaan dismantle telah diajukan. Tiket penarikan perangkat otomatis dibuat.');
    }
}
