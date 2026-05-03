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
        abort_if(auth()->user()->isSales(), 403, 'Unauthorized: Sales staff cannot perform subscriber activations.');

        // Get items that are tracked by serial number and have ready or dismantled stocks
        $serialItems = InventoryItem::where('track_serial', true)
            ->whereHas('stocks', function($q) {
                $q->whereIn('status', ['ready', 'dismantled']);
            })->with(['stocks' => function($q) {
                $q->whereIn('status', ['ready', 'dismantled']);
            }])->get();

        // Get items that are not tracked (cables, connectors, etc)
        $consumableItems = InventoryItem::where('track_serial', false)->get();

        // Get OLTs for provisioning
        $olts = \App\Models\Olt::where('is_active', true)->get();

        // Get enterprise metadata for the wizard
        $regions = \App\Models\Region::all();
        $stos = \App\Models\Sto::all();
        $stbs = \App\Models\Stb::all();
        $odcs = \App\Models\Odc::all();
        $odps = \App\Models\Odp::all();

        return view('customers.activate', compact(
            'customer', 'serialItems', 'consumableItems', 
            'regions', 'stos', 'stbs', 'odcs', 'odps', 'olts'
        ));
    }

    public function store(Request $request, Customer $customer)
    {
        abort_if(auth()->user()->isSales(), 403, 'Unauthorized: Sales staff cannot perform subscriber activations.');

        $request->validate([
            'modem_stock_id' => 'required|exists:inventory_stocks,id',
            'consumables' => 'nullable|array',
            'consumables.*.item_id' => 'required|exists:inventory_items,id',
            'consumables.*.quantity' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,transfer,pg',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            
            // Enterprise Fields
            'odc_id' => 'nullable|exists:odcs,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|numeric',
            'vlan_id' => 'nullable|numeric',
            'static_ip' => 'nullable|string',
            'region_code' => 'nullable|string',
            'sto_code' => 'nullable|string',
            'stb_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'identity_photo' => 'nullable|image|max:2048',
            'house_photo' => 'nullable|image|max:2048',
            'cpe_photo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',

            // OLT Provisioning
            'olt_id' => 'nullable|exists:olts,id',
            'onu_sn' => 'nullable|string',
            'onu_index' => 'nullable|string',
            'onu_type' => 'nullable|string',
        ]);

        DB::transaction(function() use ($request, $customer) {
            // 1. Activate Customer and Set Initial Billing
            $activatedAt = $request->input('activated_at') ? \Carbon\Carbon::parse($request->input('activated_at')) : now();
            
            $updateData = [
                'is_active' => true,
                'status' => Customer::STATUS_ACTIVE,
                'activated_at' => $activatedAt,
                'installation_paid_at' => ($request->payment_method === 'pg') ? null : now(),
                'activation_grace_expires_at' => ($request->payment_method === 'pg') ? now()->addHour() : null,
                'installation_bank_account_id' => $request->bank_account_id,
            ];

            // Load the selected modem stock to get its serial number
            $modem = InventoryStock::findOrFail($request->modem_stock_id);

            // Map and save enterprise fields from activation form
            $enterpriseFields = [
                'odc_id', 'odp_id', 'odp_port', 'vlan_id', 'static_ip',
                'region_code', 'sto_code', 'stb_code', 'latitude', 'longitude', 'description',
                'olt_id', 'onu_sn', 'onu_index', 'onu_type'
            ];

            foreach ($enterpriseFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // [PROGRES] Ensure ONU SN is taken from inventory if not provided or to ensure sync
            if (empty($updateData['onu_sn']) || $updateData['onu_sn'] === 'e.g. ZTEGC000...') {
                $updateData['onu_sn'] = $modem->serial_number;
            }

            // Handle Photo Uploads
            foreach (['identity_photo', 'house_photo', 'cpe_photo'] as $photo) {
                if ($request->hasFile($photo)) {
                    $updateData[$photo] = $request->file($photo)->store('kyc_photos', 'public');
                }
            }

            $customer->update($updateData);

            // Handle Treasury for Installation Fee (Non-PG)
            if ($customer->installation_fee > 0 && in_array($request->payment_method, ['cash', 'transfer'])) {
                // If Cash, find or create "KAS TUNAI" account if bank_account_id is null
                $targetAccountId = $request->bank_account_id;
                
                if ($request->payment_method === 'cash' && !$targetAccountId) {
                    $cashAccount = \App\Models\BankAccount::firstOrCreate(
                        ['bank_name' => 'KAS TUNAI'],
                        ['account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'is_active' => true]
                    );
                    $targetAccountId = $cashAccount->id;
                }

                if ($targetAccountId) {
                    \App\Models\BankTransaction::create([
                        'bank_account_id' => $targetAccountId,
                        'type' => 'deposit',
                        'amount' => $customer->installation_fee,
                        'description' => '[Instalasi] Pelanggan: ' . $customer->name . ' (' . $customer->customer_code . ')',
                        'transaction_date' => now(),
                        'created_by' => auth()->id(),
                    ]);
                }
            }

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

            // 3. Install Modem (Serialized Item) - Already fetched above
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

        // 4. Handle Payment Gateway Redirection if selected
        if ($request->payment_method === 'pg' && $customer->installation_fee > 0) {
            $invoice = \App\Models\Invoice::create([
                'invoice_number' => 'INV-INST-' . strtoupper(uniqid()),
                'customer_id' => $customer->id,
                'amount' => $customer->installation_fee,
                'status' => 'unpaid',
                'due_date' => now()->addDays(3),
                'description' => 'Biaya Instalasi Pelanggan: ' . $customer->name,
            ]);

            return redirect()->route('invoices.show', $invoice)->with('success', 'Aktivasi tertunda. Silakan selesaikan pembayaran instalasi via Gateway.');
        }

        return redirect()->route('customers.index')->with('success', 'Customer activated and equipment recorded successfully.');
    }

    public function dismantleForm(Customer $customer)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isTeknisi()) {
            return redirect()->route('customers.index')->with('error', 'Unauthorized: Only technicians or admins can access dismantle forms.');
        }

        $installedEquipment = InventoryStock::where('customer_id', $customer->id)
            ->where('status', 'installed')
            ->with('item')
            ->get();

        return view('customers.dismantle', compact('customer', 'installedEquipment'));
    }

    public function processDismantle(Request $request, Customer $customer)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isTeknisi()) {
            return redirect()->route('customers.index')->with('error', 'Unauthorized: Only technicians or admins can process equipment returns.');
        }

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

            // 4. External System Orchestration (After DB Transaction)
            // OLT Deprovisioning
            if ($customer->olt_id && $customer->onu_index && $customer->onu_sn) {
                \App\Jobs\DeprovisionOnuJob::dispatch($customer->olt_id, $customer->onu_index, $customer->onu_sn);
            }

            // Radius Cleanup
            if ($customer->username) {
                \App\Models\Radius\RadCheck::where('username', $customer->username)->delete();
                \App\Models\Radius\RadUserGroup::where('username', $customer->username)->delete();
                
                // Kick active session via CoA
                try {
                    $nas = \App\Models\Radius\Nas::first();
                    if ($nas) {
                        $coa = new \App\Services\RadiusCoAService();
                        $coa->disconnect($nas->nasipaddress, $nas->secret, $customer->username);
                    }
                } catch (\Exception $e) {
                    \Log::warning("CoA disconnect failed during dismantle: " . $e->getMessage());
                }
            }

            return redirect()->route('customers.index')->with('success', 'Customer dismantled and equipment returned to stock.');
        } catch (\Exception $e) {
            \Log::error('Dismantle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses dismantle: ' . $e->getMessage());
        }
    }

    public function requestDismantle(Customer $customer)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isTeknisi()) {
            return redirect()->route('customers.index')->with('error', 'Unauthorized: Only technicians or admins can initiate dismantle process.');
        }

        $customer->update(['status' => Customer::STATUS_WAITING_DISMANTLE]);

        // Auto-create Dismantle Ticket
        \App\Models\Ticket::create([
            'customer_id' => $customer->id,
            'type' => 'dismantle',
            'subject' => 'Permintaan Dismantle: ' . $customer->name,
            'description' => 'Pelanggan ini mengajukan atau dijadwalkan untuk dismantle (cabut perangkat).',
            'priority' => 'high',
            'status' => 'open'
        ]);

        return redirect()->back()->with('success', 'Permintaan dismantle telah diajukan. Tiket penarikan perangkat otomatis dibuat.');
    }
}
