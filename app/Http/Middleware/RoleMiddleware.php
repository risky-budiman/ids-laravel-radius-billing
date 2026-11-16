<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return $request->is('admin/*') || $request->is('admin') 
                ? redirect()->route('login') 
                : redirect()->route('customer.login');
        }

        if (!$request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Your account has been deactivated. Please contact the administrator.');
        }

        // Mitra yang membuka dasbor staf (/admin) diarahkan ke halaman mitranya, bukan 403
        if ($request->user()->isMitra() && $request->routeIs('dashboard')) {
            return redirect()->route('partners.show', $request->user());
        }

        // Aturan bawaan rute + pengecualian per role dari halaman "Hak Akses per Role" (App\Support\RouteAccess).
        // Rute bisa punya beberapa middleware role (grup bertingkat); keputusan dihitung atas semuanya sekaligus.
        $user = $request->user();
        $route = $request->route();
        $name = $route?->getName();
        if ($user instanceof \App\Models\User && $name) {
            $allowed = \App\Support\RouteAccess::decide($user, $name, \App\Support\RouteAccess::listsOf($route));
        } else {
            $allowed = $user->hasRole($roles);
        }
        if (! $allowed) {
            abort(403, 'Anda tidak punya akses ke halaman atau aksi ini.');
        }

        return $next($request);
    }
}
