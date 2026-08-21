<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Customer\AuthController;
use App\Http\Controllers\Api\v1\Customer\InvoiceController;
use App\Http\Controllers\Api\v1\Customer\TicketController;
use App\Http\Controllers\Api\v1\Customer\BoosterController;
use App\Http\Controllers\Api\v1\Customer\WifiController;

// Admin API Controllers
use App\Http\Controllers\Api\v1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\v1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\v1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\v1\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Api\v1\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Api\v1\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Api\v1\Admin\OnlineUserController as AdminOnlineUserController;
use App\Http\Controllers\Api\v1\Admin\NocController as AdminNocController;
use App\Http\Controllers\Api\v1\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Api\v1\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Api\v1\Admin\AccountingController as AdminAccountingController;
use App\Http\Controllers\Api\v1\Admin\SystemAdminController as AdminSystemAdminController;
use App\Http\Controllers\Api\v1\Admin\GenieAcsController as AdminGenieAcsController;

// Customer Mobile API Routes
Route::prefix('v1/customer')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/save-push-token', [AuthController::class, 'savePushToken']);

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{id}/pay', [InvoiceController::class, 'pay']);

        // Tickets
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::get('/tickets/{id}', [TicketController::class, 'show']);
        Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);
        Route::post('/tickets/{id}/close', [TicketController::class, 'close']);

        // Boosters
        Route::get('/boosters', [BoosterController::class, 'index']);
        Route::get('/boosters/{id}', [BoosterController::class, 'show']);
        Route::post('/boosters/{id}/buy', [BoosterController::class, 'buy']);

        // Wifi Management
        Route::get('/wifi/settings', [WifiController::class, 'getSettings']);
        Route::post('/wifi/settings', [WifiController::class, 'updateSettings']);
        Route::get('/wifi/device-status', [WifiController::class, 'getDeviceStatus']);
    });
});

// ============================================================
// Admin Portal API Routes
// ============================================================
Route::prefix('v1/admin')->name('api.admin.')->group(function () {
    // Public: Login
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Protected: All admin routes require Sanctum token + active staff role
    Route::middleware(['auth:sanctum', 'admin.role'])->group(function () {
        // Auth
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/profile', [AdminAuthController::class, 'profile']);

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Customers (Fase 3)
        Route::get('/customers/form-data', [AdminCustomerController::class, 'formData']);
        Route::post('/customers/bulk-action', [AdminCustomerController::class, 'bulkAction']);
        Route::post('/customers/{id}/reset-fup', [AdminCustomerController::class, 'resetFup']);
        Route::apiResource('/customers', AdminCustomerController::class);

        // Packages (Fase 4)
        Route::apiResource('/packages', AdminPackageController::class);

        // Invoices (Fase 5)
        Route::post('/invoices/bulk-mark-paid', [AdminInvoiceController::class, 'bulkMarkPaid']);
        Route::post('/invoices/bulk-delete', [AdminInvoiceController::class, 'bulkDelete']);
        Route::post('/invoices/{id}/pay', [AdminInvoiceController::class, 'pay']);
        Route::post('/invoices/{id}/whatsapp', [AdminInvoiceController::class, 'sendWhatsApp']);
        Route::apiResource('/invoices', AdminInvoiceController::class);

        // Tickets (Fase 6)
        Route::post('/tickets/{id}/claim', [AdminTicketController::class, 'claim']);
        Route::post('/tickets/{id}/reply', [AdminTicketController::class, 'reply']);
        Route::post('/tickets/{id}/close', [AdminTicketController::class, 'close']);
        Route::apiResource('/tickets', AdminTicketController::class);

        // Online Users & RADIUS (Fase 7)
        Route::get('/online-users', [AdminOnlineUserController::class, 'index']);
        Route::post('/online-users/{id}/kick', [AdminOnlineUserController::class, 'kick']);
        Route::post('/online-users/{id}/force-close', [AdminOnlineUserController::class, 'forceClose']);

        // NOC & OLT (Fase 8)
        Route::get('/noc', [AdminNocController::class, 'index']);
        Route::get('/noc/discovery', [AdminNocController::class, 'discovery']);
        Route::get('/noc/signals', [AdminNocController::class, 'signals']);
        Route::post('/noc/olts/{id}/test-connection', [AdminNocController::class, 'testConnection']);

        // Inventory & Fixed Assets (Fase 9)
        Route::get('/inventory', [AdminInventoryController::class, 'index']);
        Route::post('/inventory', [AdminInventoryController::class, 'store']);
        Route::post('/inventory/{id}/movement', [AdminInventoryController::class, 'recordMovement']);
        Route::get('/inventory/fixed-assets', [AdminInventoryController::class, 'fixedAssets']);
        Route::get('/inventory/suppliers', [AdminInventoryController::class, 'suppliers']);

        // Finance (Fase 10)
        Route::get('/finance/accounts', [AdminFinanceController::class, 'accounts']);
        Route::post('/finance/accounts', [AdminFinanceController::class, 'storeAccount']);
        Route::get('/finance/transactions', [AdminFinanceController::class, 'transactions']);
        Route::post('/finance/transaction', [AdminFinanceController::class, 'recordTransaction']);
        Route::post('/finance/transfer', [AdminFinanceController::class, 'transfer']);

        // Accounting (Fase 11)
        Route::get('/accounting/coa', [AdminAccountingController::class, 'coa']);
        Route::get('/accounting/journals', [AdminAccountingController::class, 'journals']);
        Route::get('/accounting/profit-loss', [AdminAccountingController::class, 'profitLoss']);
        Route::get('/accounting/balance-sheet', [AdminAccountingController::class, 'balanceSheet']);

        // System Admin (Fase 12)
        Route::get('/admin/users', [AdminSystemAdminController::class, 'users']);
        Route::post('/admin/users', [AdminSystemAdminController::class, 'storeUser']);
        Route::post('/admin/users/{id}/toggle-status', [AdminSystemAdminController::class, 'toggleUserStatus']);
        Route::get('/admin/settings', [AdminSystemAdminController::class, 'settings']);
        Route::post('/admin/settings', [AdminSystemAdminController::class, 'updateSettings']);
        Route::get('/admin/locations', [AdminSystemAdminController::class, 'networkLocations']);

        // GenieACS (TR-069 & CPE Management - Fase 13)
        Route::get('/genieacs/servers', [AdminGenieAcsController::class, 'servers']);
        Route::post('/genieacs/servers', [AdminGenieAcsController::class, 'storeServer']);
        Route::put('/genieacs/servers/{id}', [AdminGenieAcsController::class, 'updateServer']);
        Route::delete('/genieacs/servers/{id}', [AdminGenieAcsController::class, 'destroyServer']);
        Route::get('/genieacs/devices', [AdminGenieAcsController::class, 'devices']);
        Route::get('/genieacs/devices/{deviceId}', [AdminGenieAcsController::class, 'showDevice']);
        Route::post('/genieacs/devices/{deviceId}/reboot', [AdminGenieAcsController::class, 'rebootDevice']);
        Route::post('/genieacs/devices/{deviceId}/refresh', [AdminGenieAcsController::class, 'refreshDevice']);
        Route::post('/genieacs/devices/{deviceId}/factory-reset', [AdminGenieAcsController::class, 'factoryResetDevice']);
        Route::post('/genieacs/devices/{deviceId}/config', [AdminGenieAcsController::class, 'updateConfig']);
        Route::post('/genieacs/devices/{deviceId}/tags', [AdminGenieAcsController::class, 'updateTags']);
    });
});
