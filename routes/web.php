<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    if (auth('customer')->check()) return redirect()->route('customer.dashboard');
    if (auth('web')->check()) return redirect()->route('dashboard');
    return redirect()->route('customer.login');
});

Route::get('/login', [\App\Http\Controllers\CustomerPortal\LoginController::class, 'showLoginForm'])->name('customer.login');
Route::post('/login-client', [\App\Http\Controllers\CustomerPortal\LoginController::class, 'login'])->name('customer.login.post');
Route::post('/logout-client', [\App\Http\Controllers\CustomerPortal\LoginController::class, 'logout'])->name('customer.logout');


// Customer Portal Routes (Client Area)
Route::middleware(['auth:web,customer', 'role:customer,administrator,admin,teknisi,kasir,sales'])->prefix('client')->group(function () {
    Route::get('/', [\App\Http\Controllers\CustomerPortal\DashboardController::class, 'index'])->name('customer.dashboard');
    Route::get('/invoices', [\App\Http\Controllers\CustomerPortal\DashboardController::class, 'invoices'])->name('customer.invoices');
    Route::get('/boosters', [\App\Http\Controllers\CustomerPortal\DashboardController::class, 'boosters'])->name('customer.boosters');
    Route::post('/boosters/{booster}/buy', [\App\Http\Controllers\CustomerPortal\DashboardController::class, 'buyBooster'])->name('customer.boosters.buy');

    // Customer Ticketing
    Route::get('/tickets', [\App\Http\Controllers\CustomerPortal\TicketController::class, 'index'])->name('customer.tickets.index');
    Route::get('/tickets/create', [\App\Http\Controllers\CustomerPortal\TicketController::class, 'create'])->name('customer.tickets.create');
    Route::post('/tickets', [\App\Http\Controllers\CustomerPortal\TicketController::class, 'store'])->name('customer.tickets.store');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\CustomerPortal\TicketController::class, 'show'])->name('customer.tickets.show');
    Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\CustomerPortal\TicketController::class, 'reply'])->name('customer.tickets.reply');

    // Push Notifications
    Route::post('/push/subscribe', [\App\Http\Controllers\CustomerPortal\PushSubscriptionController::class, 'subscribe'])->name('customer.push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\CustomerPortal\PushSubscriptionController::class, 'unsubscribe'])->name('customer.push.unsubscribe');
});


// Public Customer Portal (Signed URL)
Route::get('/portal/invoice/{invoice}', [\App\Http\Controllers\PortalController::class, 'showInvoice'])
    ->name('portal.invoice')
    ->middleware('signed');

Route::post('/webhooks/midtrans', [\App\Http\Controllers\PaymentWebhookController::class, 'midtrans'])->name('webhooks.midtrans');




Route::prefix('admin')->middleware(['auth:web', 'verified', 'role:administrator,admin,teknisi,kasir,sales'])->group(function () {
    Route::get('/', function () {
        $totalSubscribers = \App\Models\Customer::count();
        
        // Active Users from billing
        $activeUsers = \App\Models\Customer::where('is_active', true)->count();
        
        // Invoice stats
        $unpaidInvoices = 0;
        $revenue = 0;
        if (class_exists(\App\Models\Invoice::class)) {
            $unpaidInvoices = \App\Models\Invoice::where('status', 'unpaid')->count();
            $revenue = \App\Models\Invoice::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('amount') ?? 0;
        }

        $latestActivities = \App\Models\ActivityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        // ── Live Traffic Data from RADIUS ──
        $onlineNow = \App\Models\Radius\RadAcct::online()->count();

        $onlineSessions = \App\Models\Radius\RadAcct::online()
            ->selectRaw('SUM(acctinputoctets) as total_upload, SUM(acctoutputoctets) as total_download')
            ->first();

        $totalUpload = $onlineSessions->total_upload ?? 0;
        $totalDownload = $onlineSessions->total_download ?? 0;

        // Top 5 users by current session traffic
        $topUsers = \App\Models\Radius\RadAcct::online()
            ->selectRaw('username, framedipaddress, acctsessiontime, (acctinputoctets + acctoutputoctets) as total_traffic, acctstarttime')
            ->orderByDesc('total_traffic')
            ->limit(5)
            ->get();

        // Hourly traffic for the last 24 hours (for chart)
        $hourlyTraffic = \App\Models\Radius\RadAcct::where('acctstarttime', '>=', now()->subHours(24))
            ->selectRaw('HOUR(acctstarttime) as hour, COUNT(*) as sessions, SUM(acctinputoctets) as upload, SUM(acctoutputoctets) as download')
            ->groupByRaw('HOUR(acctstarttime)')
            ->orderByRaw('HOUR(acctstarttime)')
            ->get()
            ->keyBy('hour');

        // Build 24-hour data array
        $chartLabels = [];
        $chartUpload = [];
        $chartDownload = [];
        $chartSessions = [];
        for ($i = 23; $i >= 0; $i--) {
            $h = now()->subHours($i)->format('H');
            $hourInt = (int) $h;
            $chartLabels[] = $h . ':00';
            $chartUpload[] = round(($hourlyTraffic[$hourInt]->upload ?? 0) / 1048576, 2);
            $chartDownload[] = round(($hourlyTraffic[$hourInt]->download ?? 0) / 1048576, 2);
            $chartSessions[] = $hourlyTraffic[$hourInt]->sessions ?? 0;
        }

        // Auth stats from radpostauth
        $authAcceptToday = \App\Models\Radius\RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Accept')->count();
        $authRejectToday = \App\Models\Radius\RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Reject')->count();

        return view('dashboard', compact(
            'totalSubscribers', 'activeUsers', 'unpaidInvoices', 'revenue', 'latestActivities',
            'onlineNow', 'totalUpload', 'totalDownload', 'topUsers',
            'chartLabels', 'chartUpload', 'chartDownload', 'chartSessions',
            'authAcceptToday', 'authRejectToday'
        ));
    })->name('dashboard');

    // RADIUS Session Management
    Route::post('radius/disconnect-all', [\App\Http\Controllers\Admin\RadiusController::class, 'disconnectAll'])->name('radius.disconnect-all');
    Route::post('radius/clear-stale', [\App\Http\Controllers\Admin\RadiusController::class, 'clearStaleSessions'])->name('radius.clear-stale');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('packages', \App\Http\Controllers\PackageController::class);

    // CUSTOMERS: All Operational Roles (View, Create, Edit)
    Route::middleware('role:administrator,admin,teknisi,sales')->group(function () {
        Route::get('customers/map', [\App\Http\Controllers\CustomerController::class, 'map'])->name('customers.map');
        Route::get('customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
        Route::patch('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update']);
        Route::post('customers/{customer}/reset-fup', [\App\Http\Controllers\CustomerController::class, 'resetFup'])->name('customers.reset-fup');
    });

    // CUSTOMER ACTIVATION: Admin & Teknisi
    Route::middleware('role:administrator,admin,teknisi')->group(function () {
        Route::get('customers/{customer}/activate', [\App\Http\Controllers\CustomerActivationController::class, 'index'])->name('customers.activate');
        Route::post('customers/{customer}/activate', [\App\Http\Controllers\CustomerActivationController::class, 'store'])->name('customers.activate.store');
        
        // Dismantle logic
        Route::post('customers/{customer}/request-dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'requestDismantle'])->name('customers.request-dismantle');
        Route::get('customers/{customer}/dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'dismantleForm'])->name('customers.dismantle');
        Route::post('customers/{customer}/dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'processDismantle'])->name('customers.dismantle.store');

        // NOC CENTER (Technician & Administrator Only)
        Route::middleware('role:administrator,teknisi')->group(function () {
            Route::get('noc', [\App\Http\Controllers\NocController::class, 'index'])->name('noc.index');
            Route::get('noc/discovery', [\App\Http\Controllers\NocController::class, 'discovery'])->name('noc.discovery');
            Route::get('noc/signals', [\App\Http\Controllers\NocController::class, 'signals'])->name('noc.signals');
        Route::get('noc/history/{customer}', [\App\Http\Controllers\NocController::class, 'history'])->name('noc.history');

            // OLT Management
            Route::resource('olts', \App\Http\Controllers\OltController::class);
            Route::post('olts/{olt}/test-connection', [\App\Http\Controllers\OltController::class, 'testConnection'])->name('olts.test-connection');
            Route::post('olts/{olt}/auto-discover-ports', [\App\Http\Controllers\OltController::class, 'autoDiscoverPorts'])->name('olts.auto-discover-ports');
            Route::post('olts/{olt}/generate-ports', [\App\Http\Controllers\OltController::class, 'generatePorts'])->name('olts.generate-ports');
            Route::post('olts/{olt}/manual-delete-onu', [\App\Http\Controllers\OltController::class, 'manualDeleteOnu'])->name('olts.manual-delete-onu');
            Route::get('olts/{olt}/ports/{port}', [\App\Http\Controllers\OltController::class, 'showPort'])->name('olts.show-port');
            Route::post('olts/{olt}/sync-all-ports', [\App\Http\Controllers\OltController::class, 'syncAllPorts'])->name('olts.sync-all-ports');
            Route::get('olts/{olt}/ports/{port}/data', [\App\Http\Controllers\OltController::class, 'getPortData'])->name('olts.get-port-data');
        });
    });

    // CUSTOMER DELETE: Admin & Administrator only
    Route::middleware('role:administrator,admin')->group(function () {
        Route::delete('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // PARTNER MANAGEMENT (B2B): Admin & Mitra
    Route::middleware('role:administrator,admin,mitra')->group(function () {
        Route::get('partners', [\App\Http\Controllers\PartnerController::class, 'index'])->name('partners.index');
        Route::get('partners/create', [\App\Http\Controllers\PartnerController::class, 'create'])->name('partners.create');
        Route::post('partners', [\App\Http\Controllers\PartnerController::class, 'store'])->name('partners.store');
        Route::get('partners/withdrawals', [\App\Http\Controllers\PartnerController::class, 'withdrawals'])->name('partners.withdrawals');
        Route::post('partners/withdrawals/request', [\App\Http\Controllers\PartnerController::class, 'requestWithdrawal'])->name('partners.withdrawals.request');
        Route::post('partners/withdrawals/{withdrawal}/process', [\App\Http\Controllers\PartnerController::class, 'processWithdrawal'])->name('partners.withdrawals.process');
        Route::get('partners/{partner}', [\App\Http\Controllers\PartnerController::class, 'show'])->name('partners.show');
        Route::get('partners/{partner}/edit', [\App\Http\Controllers\PartnerController::class, 'edit'])->name('partners.edit');
        Route::put('partners/{partner}', [\App\Http\Controllers\PartnerController::class, 'update'])->name('partners.update');
    });

    // SALES COMMISSION MANAGEMENT: Admin only
    Route::middleware('role:administrator,admin')->group(function () {
        Route::get('sales-commissions', [\App\Http\Controllers\Admin\SalesCommissionController::class, 'index'])->name('sales-commissions.index');
        Route::get('sales-commissions/{sales}', [\App\Http\Controllers\Admin\SalesCommissionController::class, 'show'])->name('sales-commissions.show');
        Route::post('sales-commissions/{sales}/withdrawals', [\App\Http\Controllers\Admin\SalesCommissionController::class, 'storeWithdrawal'])->name('sales-commissions.withdrawals.store');
    });

    // SALES STAFF PORTAL (Personal Ledger & Dashboard)
    Route::middleware('role:sales')->prefix('sales')->name('sales.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Sales\DashboardController::class, 'index'])->name('dashboard');
        Route::get('ledger', [\App\Http\Controllers\Sales\DashboardController::class, 'ledger'])->name('ledger');
        Route::get('my-customers', [\App\Http\Controllers\Sales\DashboardController::class, 'customers'])->name('customers');
    });

    // TICKETS: Admin, Teknisi & Sales
    Route::middleware('role:administrator,admin,teknisi,sales')->group(function () {
        Route::resource('tickets', \App\Http\Controllers\TicketController::class);
        Route::post('tickets/{ticket}/claim', [\App\Http\Controllers\TicketController::class, 'claim'])->name('tickets.claim');
        Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\TicketController::class, 'reply'])->name('tickets.reply');
    });

    // INVOICES & PAYMENTS: Admin & Kasir
    Route::middleware('role:administrator,admin,kasir')->group(function () {
        Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
        Route::post('invoices/generate-automated', [\App\Http\Controllers\InvoiceController::class, 'generateAutomated'])->name('invoices.generate-automated');
        Route::get('invoices/{invoice}/pay', [\App\Http\Controllers\InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('invoices/{invoice}/whatsapp', [\App\Http\Controllers\InvoiceController::class, 'sendWhatsApp'])->name('invoices.whatsapp');
    });

    // TECHNICAL & WAREHOUSE: Admin & Teknisi
    Route::middleware('role:administrator,admin,teknisi')->group(function () {
        Route::get('online-users', [\App\Http\Controllers\OnlineUserController::class, 'index'])->name('online-users.index');
        Route::post('online-users/{radacctid}/kick', [\App\Http\Controllers\OnlineUserController::class, 'kick'])->name('online-users.kick');
        Route::post('online-users/{radacctid}/force-close', [\App\Http\Controllers\OnlineUserController::class, 'forceClose'])->name('online-users.force-close');
        Route::get('auth-logs', [\App\Http\Controllers\AuthLogController::class, 'index'])->name('auth-logs.index');
        Route::delete('auth-logs/clear', [\App\Http\Controllers\AuthLogController::class, 'clear'])->name('auth-logs.clear');
        Route::delete('auth-logs/{id}', [\App\Http\Controllers\AuthLogController::class, 'destroy'])->name('auth-logs.destroy');
        
        // Inventory - View & Stock Management (Technician/Admin/Administrator)
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::get('inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/categories', [\App\Http\Controllers\InventoryController::class, 'categories'])->name('inventory.categories');
        Route::get('inventory/stock-in', [\App\Http\Controllers\InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('inventory/stock-in', [\App\Http\Controllers\InventoryController::class, 'storeStockIn'])->name('inventory.stock-in.store');
        Route::get('inventory/stock-out', [\App\Http\Controllers\InventoryController::class, 'stockOut'])->name('inventory.stock-out');
        Route::post('inventory/stock-out', [\App\Http\Controllers\InventoryController::class, 'storeStockOut'])->name('inventory.stock-out.store');
        Route::get('inventory/outflow', [\App\Http\Controllers\InventoryController::class, 'outflowReport'])->name('inventory.outflow');

        // Inventory - Management (Admin & Administrator)
        Route::middleware('role:administrator,admin')->group(function () {
            Route::get('inventory/items/create', [\App\Http\Controllers\InventoryController::class, 'create'])->name('inventory.create');
            Route::post('inventory/items', [\App\Http\Controllers\InventoryController::class, 'store'])->name('inventory.store');
            Route::get('inventory/categories/create', [\App\Http\Controllers\InventoryController::class, 'createCategory'])->name('inventory.category.create');
            Route::post('inventory/categories', [\App\Http\Controllers\InventoryController::class, 'storeCategory'])->name('inventory.category.store');
        });

        // Wildcard routes must come AFTER static routes
        Route::get('inventory/items/{item}', [\App\Http\Controllers\InventoryController::class, 'show'])->name('inventory.show');

        // Inventory - Restrictive Management (Administrator Only)
        Route::middleware('role:administrator')->group(function () {
            Route::get('inventory/items/{item}/edit', [\App\Http\Controllers\InventoryController::class, 'edit'])->name('inventory.edit');
            Route::put('inventory/items/{item}', [\App\Http\Controllers\InventoryController::class, 'update'])->name('inventory.update');
            Route::delete('inventory/items/{item}', [\App\Http\Controllers\InventoryController::class, 'destroy'])->name('inventory.destroy');

            Route::get('inventory/categories/{category}/edit', [\App\Http\Controllers\InventoryController::class, 'editCategory'])->name('inventory.category.edit');
            Route::put('inventory/categories/{category}', [\App\Http\Controllers\InventoryController::class, 'updateCategory'])->name('inventory.category.update');
            Route::delete('inventory/categories/{category}', [\App\Http\Controllers\InventoryController::class, 'destroyCategory'])->name('inventory.category.destroy');
        });

        // Fixed Assets
        Route::resource('fixed-assets', \App\Http\Controllers\FixedAssetController::class);
    });
    // APP CHANGELOG (Public/Shared)
    Route::get('changelog', [\App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog.index');
    
    // Finance Module (Shared Access)
    Route::group(['prefix' => 'finance', 'middleware' => 'role:administrator,admin,kasir,teknisi'], function () {
        // View Bank Accounts & Transactions
        Route::get('bank-accounts', [\App\Http\Controllers\Finance\BankAccountController::class, 'index'])->name('bank-accounts.index');
        
        // Administrator-Only Management
        Route::middleware('role:administrator,admin')->group(function () {
            Route::get('bank-accounts/create', [\App\Http\Controllers\Finance\BankAccountController::class, 'create'])->name('bank-accounts.create');
            Route::post('bank-accounts', [\App\Http\Controllers\Finance\BankAccountController::class, 'store'])->name('bank-accounts.store');
            Route::get('bank-accounts/{bankAccount}/edit', [\App\Http\Controllers\Finance\BankAccountController::class, 'edit'])->name('bank-accounts.edit');
            Route::put('bank-accounts/{bankAccount}', [\App\Http\Controllers\Finance\BankAccountController::class, 'update'])->name('bank-accounts.update');
            Route::delete('bank-accounts/{bankAccount}', [\App\Http\Controllers\Finance\BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

            Route::get('transfer', [\App\Http\Controllers\Finance\BankTransactionController::class, 'transfer'])->name('bank-transactions.transfer');
            Route::post('transfer', [\App\Http\Controllers\Finance\BankTransactionController::class, 'processTransfer'])->name('bank-transactions.process-transfer');
            
            Route::get('expense', [\App\Http\Controllers\Finance\BankTransactionController::class, 'expense'])->name('bank-transactions.expense');
            Route::post('expense', [\App\Http\Controllers\Finance\BankTransactionController::class, 'processExpense'])->name('bank-transactions.process-expense');

            Route::get('income', [\App\Http\Controllers\Finance\BankTransactionController::class, 'income'])->name('bank-transactions.income');
            Route::post('income', [\App\Http\Controllers\Finance\BankTransactionController::class, 'processIncome'])->name('bank-transactions.process-income');
            
            Route::delete('transactions/{bankTransaction}', [\App\Http\Controllers\Finance\BankTransactionController::class, 'destroy'])->name('bank-transactions.destroy');
        });

        Route::get('bank-accounts/{bankAccount}', [\App\Http\Controllers\Finance\BankAccountController::class, 'show'])->name('bank-accounts.show');

        // Staff/Cashier Specific Actions
        Route::middleware('role:admin,kasir,teknisi')->group(function () {
            Route::get('cashier-deposit', [\App\Http\Controllers\Finance\BankTransactionController::class, 'depositToCompany'])->name('bank-transactions.cashier-deposit');
            Route::post('cashier-deposit', [\App\Http\Controllers\Finance\BankTransactionController::class, 'processDepositToCompany'])->name('bank-transactions.process-cashier-deposit');
        });
    });

    // SYSTEM ADMINISTRATION: Administrator ONLY
    Route::middleware('role:administrator')->group(function () {
        Route::resource('nas', \App\Http\Controllers\NasController::class);
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::post('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/reset-sessions', [\App\Http\Controllers\UserController::class, 'resetSessions'])->name('users.reset-sessions');
        
        Route::resource('packages', \App\Http\Controllers\PackageController::class);
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('server-logs', [\App\Http\Controllers\ServerLogController::class, 'index'])->name('server-logs.index');
        Route::delete('server-logs/clear', [\App\Http\Controllers\ServerLogController::class, 'clear'])->name('server-logs.clear');

        // WhatsApp Templates
        Route::get('whatsapp-templates', [\App\Http\Controllers\WhatsappTemplateController::class, 'index'])->name('whatsapp-templates.index');
        Route::get('whatsapp-templates/{whatsappTemplate}/edit', [\App\Http\Controllers\WhatsappTemplateController::class, 'edit'])->name('whatsapp-templates.edit');
        Route::put('whatsapp-templates/{whatsappTemplate}', [\App\Http\Controllers\WhatsappTemplateController::class, 'update'])->name('whatsapp-templates.update');
        Route::post('whatsapp-templates/reset', [\App\Http\Controllers\WhatsappTemplateController::class, 'reset'])->name('whatsapp-templates.reset');

        // WhatsApp Broadcast
        Route::get('whatsapp-broadcast', [\App\Http\Controllers\WhatsappBroadcastController::class, 'create'])->name('whatsapp-broadcast.create');
        Route::post('whatsapp-broadcast', [\App\Http\Controllers\WhatsappBroadcastController::class, 'send'])->name('whatsapp-broadcast.send');

        // WhatsApp Logs
        Route::get('whatsapp-logs', [\App\Http\Controllers\WhatsappLogController::class, 'index'])->name('whatsapp-logs.index');
        Route::post('whatsapp-logs/{whatsappLog}/resend', [\App\Http\Controllers\WhatsappLogController::class, 'resend'])->name('whatsapp-logs.resend');
        
        // Location Master Data
        Route::get('locations/regions', [\App\Http\Controllers\LocationDataController::class, 'regions'])->name('locations.regions');
        Route::post('locations/regions', [\App\Http\Controllers\LocationDataController::class, 'storeRegion'])->name('locations.region.store');
        Route::put('locations/regions/{region}', [\App\Http\Controllers\LocationDataController::class, 'updateRegion'])->name('locations.region.update');
        Route::delete('locations/regions/{region}', [\App\Http\Controllers\LocationDataController::class, 'destroyRegion'])->name('locations.region.destroy');
        Route::get('locations/stos', [\App\Http\Controllers\LocationDataController::class, 'stos'])->name('locations.stos');
        Route::post('locations/stos', [\App\Http\Controllers\LocationDataController::class, 'storeSto'])->name('locations.sto.store');
        Route::put('locations/stos/{sto}', [\App\Http\Controllers\LocationDataController::class, 'updateSto'])->name('locations.sto.update');
        Route::delete('locations/stos/{sto}', [\App\Http\Controllers\LocationDataController::class, 'destroySto'])->name('locations.sto.destroy');
        Route::get('locations/stbs', [\App\Http\Controllers\LocationDataController::class, 'stbs'])->name('locations.stbs');
        Route::post('locations/stbs', [\App\Http\Controllers\LocationDataController::class, 'storeStb'])->name('locations.stb.store');
        Route::put('locations/stbs/{stb}', [\App\Http\Controllers\LocationDataController::class, 'updateStb'])->name('locations.stb.update');
        Route::delete('locations/stbs/{stb}', [\App\Http\Controllers\LocationDataController::class, 'destroyStb'])->name('locations.stb.destroy');
        
        Route::get('locations/odcs', [\App\Http\Controllers\LocationDataController::class, 'odcs'])->name('locations.odcs');
        Route::post('locations/odcs', [\App\Http\Controllers\LocationDataController::class, 'storeOdc'])->name('locations.odc.store');
        Route::put('locations/odcs/{odc}', [\App\Http\Controllers\LocationDataController::class, 'updateOdc'])->name('locations.odc.update');
        Route::delete('locations/odcs/{odc}', [\App\Http\Controllers\LocationDataController::class, 'destroyOdc'])->name('locations.odc.destroy');
        
        Route::get('locations/odps', [\App\Http\Controllers\LocationDataController::class, 'odps'])->name('locations.odps');
        Route::post('locations/odps', [\App\Http\Controllers\LocationDataController::class, 'storeOdp'])->name('locations.odp.store');
        Route::put('locations/odps/{odp}', [\App\Http\Controllers\LocationDataController::class, 'updateOdp'])->name('locations.odp.update');
        Route::delete('locations/odps/{odp}', [\App\Http\Controllers\LocationDataController::class, 'destroyOdp'])->name('locations.odp.destroy');

        // Integrations & Settings
        Route::get('integrations/payment', [\App\Http\Controllers\IntegrationController::class, 'payment'])->name('integrations.payment');
        Route::get('integrations/whatsapp', [\App\Http\Controllers\IntegrationController::class, 'whatsapp'])->name('integrations.whatsapp');
        Route::get('integrations/noc-bot', [\App\Http\Controllers\IntegrationController::class, 'nocBot'])->name('integrations.noc-bot');
        Route::post('integrations/update', [\App\Http\Controllers\IntegrationController::class, 'update'])->name('integrations.update');
        Route::post('integrations/noc-bot', [\App\Http\Controllers\IntegrationController::class, 'updateNocBot'])->name('integrations.noc-bot.update');
        Route::get('acs-devices/details/{deviceId}', [\App\Http\Controllers\AcsServerController::class, 'deviceDetails'])->name('acs-servers.device-details')->where('deviceId', '[a-zA-Z0-9\-\.]+');
        Route::get('acs-devices/{deviceId}/show', [\App\Http\Controllers\AcsServerController::class, 'showDeviceRaw'])->name('acs-servers.show-device')->where('deviceId', '[a-zA-Z0-9\-\.]+');
        Route::post('acs-devices/{deviceId}/update-config', [\App\Http\Controllers\AcsServerController::class, 'updateConfig'])->name('acs-servers.update-config')->where('deviceId', '[a-zA-Z0-9\-\.]+');
        Route::post('acs-devices/{deviceId}/reboot', [\App\Http\Controllers\AcsServerController::class, 'reboot'])->name('acs-servers.reboot')->where('deviceId', '[a-zA-Z0-9\-\.]+');
        Route::post('acs-devices/{deviceId}/refresh', [\App\Http\Controllers\AcsServerController::class, 'refreshDevice'])->name('acs-servers.refresh-device')->where('deviceId', '[a-zA-Z0-9\-\.]+');
        Route::get('acs-devices', [\App\Http\Controllers\AcsServerController::class, 'devices'])->name('acs-servers.devices');
        Route::resource('acs-servers', \App\Http\Controllers\AcsServerController::class);
        Route::get('settings/company', [\App\Http\Controllers\CompanySettingsController::class, 'index'])->name('settings.company');
        Route::post('settings/company', [\App\Http\Controllers\CompanySettingsController::class, 'update'])->name('settings.company.update');

        // Changelog Management
        Route::post('changelog', [\App\Http\Controllers\ChangelogController::class, 'store'])->name('changelog.store');
        Route::delete('changelog/{changelog}', [\App\Http\Controllers\ChangelogController::class, 'destroy'])->name('changelog.destroy');
    });

    // Shared Utility APIs (Auth only)
    Route::get('api/regions/{region}/stos', [\App\Http\Controllers\LocationDataController::class, 'apiStos']);
    Route::get('api/stos/{sto}/stbs', [\App\Http\Controllers\LocationDataController::class, 'apiStbs']);
    Route::get('api/search', [\App\Http\Controllers\SearchController::class, 'apiSearch'])->name('api.search');
    Route::get('api/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('api.notifications');
    Route::post('api/notifications/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    // ACCOUNTING SYSTEM (Admin & Administrator Only)
    Route::middleware('role:admin,administrator')->prefix('accounting')->name('accounting.')->group(function () {
        Route::resource('coa', \App\Http\Controllers\Accounting\ChartOfAccountController::class)->only(['index', 'store', 'update', 'destroy']);
        
        Route::get('journals', [\App\Http\Controllers\Accounting\JournalController::class, 'index'])->name('journals.index');
        Route::get('journals/create', [\App\Http\Controllers\Accounting\JournalController::class, 'create'])->name('journals.create');
        Route::post('journals', [\App\Http\Controllers\Accounting\JournalController::class, 'store'])->name('journals.store');
        Route::get('journals/{journal}', [\App\Http\Controllers\Accounting\JournalController::class, 'show'])->name('journals.show');
        Route::delete('journals/{journal}', [\App\Http\Controllers\Accounting\JournalController::class, 'destroy'])->name('journals.destroy');
        
        // Reports
        Route::get('reports/profit-loss', [\App\Http\Controllers\Accounting\ReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('reports/balance-sheet', [\App\Http\Controllers\Accounting\ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('reports/tax-summary', [\App\Http\Controllers\Accounting\ReportController::class, 'taxSummary'])->name('reports.tax-summary');
        Route::get('reports/ledger', [\App\Http\Controllers\Accounting\ReportController::class, 'ledger'])->name('reports.ledger');
        Route::get('reports/ledger/export', [\App\Http\Controllers\Accounting\ReportController::class, 'exportLedger'])->name('reports.ledger.export');
        Route::get('reports/cash-flow', [\App\Http\Controllers\Accounting\ReportController::class, 'cashFlow'])->name('reports.cash-flow');
        Route::get('reports/cash-flow/export', [\App\Http\Controllers\Accounting\ReportController::class, 'exportCashFlow'])->name('reports.cash-flow.export');

        Route::get('closing', [\App\Http\Controllers\Accounting\ClosingController::class, 'index'])->name('closing.index');
        Route::post('closing', [\App\Http\Controllers\Accounting\ClosingController::class, 'process'])->name('closing.process');

        Route::get('tax-settings', [\App\Http\Controllers\Accounting\TaxSettingController::class, 'index'])->name('tax-settings.index');
        Route::post('tax-settings', [\App\Http\Controllers\Accounting\TaxSettingController::class, 'update'])->name('tax-settings.update');
        
        Route::resource('taxes', \App\Http\Controllers\Accounting\TaxController::class);
    });

    // Documentation System
    Route::get('/docs/{page?}', [\App\Http\Controllers\DocsController::class, 'index'])->name('docs.index');
});

// Admin Auth Routes
Route::prefix('admin')->group(function () {
    require __DIR__.'/auth.php';
});

Route::get('/diagnostic', [\App\Http\Controllers\DiagnosticController::class, 'index'])->middleware(['auth:web', 'role:administrator']);

Route::get('/test-ping', function() { return 'PONG'; });
