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

    return view('dashboard', compact('totalSubscribers', 'activeUsers', 'unpaidInvoices', 'revenue'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Management Routes
    Route::resource('customers', \App\Http\Controllers\CustomerController::class);
    Route::get('customers/{customer}/activate', [\App\Http\Controllers\CustomerActivationController::class, 'index'])->name('customers.activate');
    Route::post('customers/{customer}/activate', [\App\Http\Controllers\CustomerActivationController::class, 'store'])->name('customers.activate.store');
    Route::get('customers/{customer}/dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'dismantleForm'])->name('customers.dismantle');
    Route::post('customers/{customer}/dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'processDismantle'])->name('customers.dismantle.store');
    Route::post('customers/{customer}/request-dismantle', [\App\Http\Controllers\CustomerActivationController::class, 'requestDismantle'])->name('customers.request-dismantle');
    Route::resource('packages', \App\Http\Controllers\PackageController::class);
    Route::resource('nas', \App\Http\Controllers\NasController::class);
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::get('invoices/{invoice}/pay', [\App\Http\Controllers\InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::post('invoices/{invoice}/whatsapp', [\App\Http\Controllers\InvoiceController::class, 'sendWhatsApp'])->name('invoices.whatsapp');
    Route::resource('tickets', \App\Http\Controllers\TicketController::class);

    Route::get('online-users', [\App\Http\Controllers\OnlineUserController::class, 'index'])->name('online-users.index');

    // Inventory & Supplier Routes
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
    Route::get('inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/items/create', [\App\Http\Controllers\InventoryController::class, 'create'])->name('inventory.create');
    Route::post('inventory/items', [\App\Http\Controllers\InventoryController::class, 'store'])->name('inventory.store');
    Route::get('inventory/items/{item}', [\App\Http\Controllers\InventoryController::class, 'show'])->name('inventory.show');
    Route::get('inventory/categories', [\App\Http\Controllers\InventoryController::class, 'categories'])->name('inventory.categories');
    Route::post('inventory/categories', [\App\Http\Controllers\InventoryController::class, 'storeCategory'])->name('inventory.category.store');
    
    // Low level stock entry
    Route::get('inventory/stock-in', [\App\Http\Controllers\InventoryController::class, 'stockIn'])->name('inventory.stock-in');
    Route::post('inventory/stock-in', [\App\Http\Controllers\InventoryController::class, 'storeStockIn'])->name('inventory.stock-in.store');

    // Location Master Data Routes
    Route::get('locations/regions', [\App\Http\Controllers\LocationDataController::class, 'regions'])->name('locations.regions');
    Route::post('locations/regions', [\App\Http\Controllers\LocationDataController::class, 'storeRegion'])->name('locations.region.store');
    Route::delete('locations/regions/{region}', [\App\Http\Controllers\LocationDataController::class, 'destroyRegion'])->name('locations.region.destroy');
    
    Route::get('locations/stos', [\App\Http\Controllers\LocationDataController::class, 'stos'])->name('locations.stos');
    Route::post('locations/stos', [\App\Http\Controllers\LocationDataController::class, 'storeSto'])->name('locations.sto.store');
    Route::delete('locations/stos/{sto}', [\App\Http\Controllers\LocationDataController::class, 'destroySto'])->name('locations.sto.destroy');
    
    Route::get('locations/stbs', [\App\Http\Controllers\LocationDataController::class, 'stbs'])->name('locations.stbs');
    Route::post('locations/stbs', [\App\Http\Controllers\LocationDataController::class, 'storeStb'])->name('locations.stb.store');
    Route::delete('locations/stbs/{stb}', [\App\Http\Controllers\LocationDataController::class, 'destroyStb'])->name('locations.stb.destroy');

    // JSON API Endpoints for Cascading Dropdown
    Route::get('api/regions/{region}/stos', [\App\Http\Controllers\LocationDataController::class, 'apiStos']);
    Route::get('api/stos/{sto}/stbs', [\App\Http\Controllers\LocationDataController::class, 'apiStbs']);

    // Integration Gateways
    Route::get('integrations/payment', [\App\Http\Controllers\IntegrationController::class, 'payment'])->name('integrations.payment');
    Route::get('integrations/whatsapp', [\App\Http\Controllers\IntegrationController::class, 'whatsapp'])->name('integrations.whatsapp');
    Route::post('integrations/update', [\App\Http\Controllers\IntegrationController::class, 'update'])->name('integrations.update');
});

require __DIR__.'/auth.php';
