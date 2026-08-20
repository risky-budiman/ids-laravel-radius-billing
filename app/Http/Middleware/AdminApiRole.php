<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiRole
{
    /**
     * Handle an incoming API request — validate staff role.
     * Returns JSON 403 instead of redirect (unlike RoleMiddleware for web).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if (!$user->is_active) {
            // Revoke the token
            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan.'
            ], 403);
        }

        // If specific roles are passed, check them
        if (!empty($roles) && !$user->hasRole($roles)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk fitur ini.'
            ], 403);
        }

        return $next($request);
    }
}
