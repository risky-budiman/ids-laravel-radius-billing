<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Customer\AuthController;
use App\Http\Controllers\Api\v1\Customer\InvoiceController;
use App\Http\Controllers\Api\v1\Customer\TicketController;
use App\Http\Controllers\Api\v1\Customer\BoosterController;
use App\Http\Controllers\Api\v1\Customer\WifiController;

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
