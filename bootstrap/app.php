<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/webhooks/midtrans',
            '/webhooks/xendit',
            '/webhooks/duitku',
            '/webhooks/moota',
        ]);
        
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin.role' => \App\Http\Middleware\AdminApiRole::class,
        ]);

        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin/*') || $request->is('admin') ? route('login') : route('customer.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            // Tangkap foreign key constraint (SQLSTATE 23000 / Error 1451)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                $friendlyMessage = 'Data ini tidak dapat dihapus atau diubah karena masih digunakan atau terhubung dengan data lain (misalnya pelanggan, tagihan, atau transaksi aktif).';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $friendlyMessage,
                    ], 422);
                }

                return back()->with('error', $friendlyMessage);
            }

            // Untuk web request biasa, hindari menampilkan pesan mentah SQL syntax
            if (!$request->expectsJson() && !app()->isLocal()) {
                return back()->with('error', 'Terjadi kesalahan pada database saat memproses data.');
            }
        });
    })->create();
