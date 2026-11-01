<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

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
            $errorCode = $e->errorInfo[1] ?? null;

            // MySQL Error 1451: Cannot delete or update a parent row (Foreign Key RESTRICT)
            if ($errorCode == 1451 || str_contains($e->getMessage(), '1451 Cannot delete or update a parent row')) {
                $friendlyMessage = 'Data ini tidak dapat dihapus atau diubah karena masih digunakan atau terhubung dengan data lain (misalnya pelanggan, tagihan, atau transaksi aktif).';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $friendlyMessage,
                    ], 422);
                }

                return back()->with('error', $friendlyMessage);
            }

            // MySQL Error 1062: Duplicate entry
            if ($errorCode == 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                $friendlyMessage = 'Data yang dimasukkan sudah terdaftar di sistem (duplikat). Silakan periksa kembali.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $friendlyMessage,
                    ], 422);
                }

                return back()->with('error', $friendlyMessage);
            }

            // Untuk web request biasa, hindari menampilkan pesan mentah SQL syntax di production
            if (!$request->expectsJson() && !app()->isLocal()) {
                return back()->with('error', 'Terjadi kesalahan pada database saat memproses data.');
            }
        });
    })->create();
