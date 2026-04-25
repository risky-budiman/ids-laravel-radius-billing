<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Customer Portal (Signed URL)
Route::get('/portal/invoice/{invoice}', [\App\Http\Controllers\PortalController::class, 'showInvoice'])
    ->name('portal.invoice')
    ->middleware('signed');

Route::get('/dashboard', function () {
    $totalSubscribers = \App\Models\Customer::count();
    
    // Simulate Active Users based on is_active for now or radacct if available
    $activeUsers = \App\Models\Customer::where('is_active', true)->count();
    
    // For invoices we can try to fetch, if it exists
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

    return view('dashboard', compact('totalSubscribers', 'activeUsers', 'unpaidInvoices', 'revenue', 'latestActivities'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Universal Operational Routes (Multiple Roles)
    
    // CUSTOMERS: All Operational Roles (View, Create, Edit)
    Route::middleware('role:administrator,admin,teknisi,sales')->group(function () {
        Route::get('customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
        Route::patch('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update']);
    });

    // CUSTOMER ACTIVATION: Admin & Teknisi
    Route::middleware('role:administrator,admin,teknisi')->group(function () {
        Route::get('customers/{customer}/activate', [\App\Http\Controllers\CustomerActivationController::class, 'index'])->name('customers.activate');
        Route::post('customers/{customer}/activate', [\App\Http\Controllers\CustomerActivationController::class, 'store'])->name('customers.activate.store');
        
        // Dismantle logic
        Route::post('customers/{customer}/request-dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'requestDismantle'])->name('customers.request-dismantle');
        Route::get('customers/{customer}/dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'dismantleForm'])->name('customers.dismantle');
        Route::post('customers/{customer}/dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'processDismantle'])->name('customers.dismantle.store');
    });

    // CUSTOMER DELETE: Admin & Administrator only
    Route::middleware('role:administrator,admin')->group(function () {
        Route::delete('customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // TICKETS: Admin, Teknisi & Sales
    Route::middleware('role:administrator,admin,teknisi,sales')->group(function () {
        Route::resource('tickets', \App\Http\Controllers\TicketController::class);
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
        
        // Inventory - View & Stock Management (Technician/Admin/Administrator)
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
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
    });
    // APP CHANGELOG (Public/Shared)
    Route::get('changelog', [\App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog.index');

    // SYSTEM ADMINISTRATION: Administrator ONLY
    Route::middleware('role:administrator')->group(function () {
        Route::resource('nas', \App\Http\Controllers\NasController::class);
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::post('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/reset-sessions', [\App\Http\Controllers\UserController::class, 'resetSessions'])->name('users.reset-sessions');
        
        Route::resource('packages', \App\Http\Controllers\PackageController::class);
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
        
        // Location Master Data
        Route::get('locations/regions', [\App\Http\Controllers\LocationDataController::class, 'regions'])->name('locations.regions');
        Route::post('locations/regions', [\App\Http\Controllers\LocationDataController::class, 'storeRegion'])->name('locations.region.store');
        Route::delete('locations/regions/{region}', [\App\Http\Controllers\LocationDataController::class, 'destroyRegion'])->name('locations.region.destroy');
        Route::get('locations/stos', [\App\Http\Controllers\LocationDataController::class, 'stos'])->name('locations.stos');
        Route::post('locations/stos', [\App\Http\Controllers\LocationDataController::class, 'storeSto'])->name('locations.sto.store');
        Route::delete('locations/stos/{sto}', [\App\Http\Controllers\LocationDataController::class, 'destroySto'])->name('locations.sto.destroy');
        Route::get('locations/stbs', [\App\Http\Controllers\LocationDataController::class, 'stbs'])->name('locations.stbs');
        Route::post('locations/stbs', [\App\Http\Controllers\LocationDataController::class, 'storeStb'])->name('locations.stb.store');
        Route::delete('locations/stbs/{stb}', [\App\Http\Controllers\LocationDataController::class, 'destroyStb'])->name('locations.stb.destroy');

        // Integrations & Settings
        Route::get('integrations/payment', [\App\Http\Controllers\IntegrationController::class, 'payment'])->name('integrations.payment');
        Route::get('integrations/whatsapp', [\App\Http\Controllers\IntegrationController::class, 'whatsapp'])->name('integrations.whatsapp');
        Route::post('integrations/update', [\App\Http\Controllers\IntegrationController::class, 'update'])->name('integrations.update');
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
});

require __DIR__.'/auth.php';
