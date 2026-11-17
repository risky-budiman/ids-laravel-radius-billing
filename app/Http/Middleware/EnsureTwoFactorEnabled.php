<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bila verifikasi dua langkah diwajibkan untuk role staf (Kelola Staf), akun yang belum mengaktifkannya hanya
 * bisa membuka Profil (untuk mengaktifkan) dan logout.
 */
class EnsureTwoFactorEnabled
{
    private const ALLOWED = ['profile.edit', 'profile.update', 'two-factor.*', 'logout', 'password.*', 'docs.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $user->role === User::ROLE_CUSTOMER || $user->hasTwoFactor()
            || $request->routeIs(...self::ALLOWED) || ! TwoFactorService::requiredFor($user)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Aktifkan verifikasi dua langkah di Profil terlebih dahulu.'], 403);
        }

        return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah')
            ->with('warning', 'Verifikasi dua langkah wajib untuk akun Anda. Aktifkan dulu sebelum melanjutkan.');
    }
}
