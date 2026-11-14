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
        // Hanya proxy yang benar-benar di depan aplikasi yang boleh menentukan IP klien lewat
        // X-Forwarded-For. Dulu '*' (siapa saja): batas percobaan login per IP bisa ditembus
        // dengan memalsukan header. Default: nginx lokal. Di belakang Cloudflare / load balancer,
        // isi TRUSTED_PROXIES di .env dengan rentang IP-nya (dipisah koma), atau '*' bila
        // memang semua lalu lintas melewati proxy tersebut.
        $trusted = trim((string) env('TRUSTED_PROXIES', '127.0.0.1,::1'));
        $middleware->trustProxies(at: $trusted === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $trusted)))));

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

            // Untuk pengiriman form, kembalikan ke halaman sebelumnya dengan pesan ramah.
            // Untuk GET (membuka halaman) jangan redirect: back() bisa berputar-putar
            // (halaman error -> / -> dashboard -> error ...). Biarkan halaman 500 tampil & error tercatat di log.
            if (!$request->expectsJson() && !app()->isLocal() && !$request->isMethod('GET')) {
                return back()->with('error', 'Terjadi kesalahan pada database saat memproses data.');
            }
        });
    })->create();
