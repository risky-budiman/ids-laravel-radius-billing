<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiRole
{
    /**
     * Role staf yang boleh memakai API admin sama sekali.
     */
    public const STAFF_ROLES = [
        User::ROLE_ADMINISTRATOR,
        User::ROLE_ADMIN,
        User::ROLE_TEKNISI,
        User::ROLE_KASIR,
        User::ROLE_SALES,
        User::ROLE_MITRA,
    ];

    /**
     * Validasi token API admin: harus milik staf (App\Models\User), aktif,
     * dan — bila role diberikan (admin.role:administrator,admin) — memiliki salah satunya.
     * Mengembalikan JSON 401/403, bukan redirect.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Token Sanctum bisa milik model apa pun (mis. Customer dari API pelanggan).
        // API admin hanya untuk akun staf.
        if (! $user instanceof User || ! $user->hasRole(self::STAFF_ROLES)) {
            return response()->json(['message' => 'Token ini tidak memiliki akses ke API admin.'], 403);
        }

        if (! $user->is_active) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'Akun Anda telah dinonaktifkan.'], 403);
        }

        if (! empty($roles) && ! $user->hasRole($roles)) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk fitur ini.'], 403);
        }

        return $next($request);
    }
}
